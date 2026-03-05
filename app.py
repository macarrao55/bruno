import csv
import io
import os
import sqlite3
from datetime import datetime, date
from functools import wraps

from flask import (
    Flask,
    flash,
    g,
    redirect,
    render_template,
    request,
    send_file,
    session,
    url_for,
    jsonify,
)
from werkzeug.security import check_password_hash, generate_password_hash

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(BASE_DIR, "data", "mega_lanches.db")

app = Flask(__name__)
app.secret_key = os.environ.get("SECRET_KEY", "mega-lanches-secret-change-me")


def get_db():
    if "db" not in g:
        g.db = sqlite3.connect(DB_PATH)
        g.db.row_factory = sqlite3.Row
    return g.db


@app.teardown_appcontext
def close_db(_=None):
    db = g.pop("db", None)
    if db:
        db.close()


def query_db(query, params=(), one=False):
    cur = get_db().execute(query, params)
    rows = cur.fetchall()
    cur.close()
    return (rows[0] if rows else None) if one else rows


def execute_db(query, params=()):
    db = get_db()
    cur = db.execute(query, params)
    db.commit()
    return cur.lastrowid


def audit(action, module, details=""):
    uid = session.get("user_id")
    execute_db(
        "INSERT INTO audit_logs (user_id, action, module, details, created_at) VALUES (?,?,?,?,?)",
        (uid, action, module, details[:400], datetime.now().isoformat()),
    )


def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if not session.get("user_id"):
            return redirect(url_for("login"))
        return f(*args, **kwargs)

    return decorated


def role_required(*roles):
    def wrapper(f):
        @wraps(f)
        def decorated(*args, **kwargs):
            if session.get("role") not in roles:
                flash("Acesso negado para seu perfil.", "error")
                return redirect(url_for("dashboard"))
            return f(*args, **kwargs)

        return decorated

    return wrapper


def init_db():
    os.makedirs(os.path.join(BASE_DIR, "data"), exist_ok=True)
    db = sqlite3.connect(DB_PATH)
    with open(os.path.join(BASE_DIR, "schema.sql"), "r", encoding="utf-8") as f:
        db.executescript(f.read())
    # Seed only when no users
    c = db.execute("SELECT COUNT(*) FROM users")
    if c.fetchone()[0] == 0:
        with open(os.path.join(BASE_DIR, "seeds.sql"), "r", encoding="utf-8") as f:
            sql = f.read().replace("{{ADMIN_HASH}}", generate_password_hash("admin123"))
            db.executescript(sql)
    db.commit()
    db.close()


@app.route("/")
def index():
    if session.get("user_id"):
        return redirect(url_for("dashboard"))
    return redirect(url_for("login"))


@app.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        username = request.form.get("username", "").strip()
        password = request.form.get("password", "")
        user = query_db("SELECT * FROM users WHERE username=? AND active=1", (username,), one=True)
        if user and check_password_hash(user["password_hash"], password):
            session["user_id"] = user["id"]
            session["username"] = user["name"]
            session["role"] = user["role"]
            audit("LOGIN", "AUTH", f"Usuário {username} entrou")
            return redirect(url_for("dashboard"))
        flash("Usuário ou senha inválidos.", "error")
    return render_template("login.html")


@app.route("/logout")
@login_required
def logout():
    audit("LOGOUT", "AUTH", f"Usuário {session.get('username')} saiu")
    session.clear()
    return redirect(url_for("login"))


@app.context_processor
def inject_globals():
    config = query_db("SELECT key, value FROM settings")
    settings = {c["key"]: c["value"] for c in config}
    return {"current_user": session.get("username"), "current_role": session.get("role"), "settings": settings}


@app.route("/dashboard")
@login_required
def dashboard():
    today = date.today().isoformat()
    daily_sales = query_db("SELECT COALESCE(SUM(total),0) t, COUNT(*) c FROM orders WHERE date(created_at)=?", (today,), one=True)
    month_sales = query_db(
        "SELECT COALESCE(SUM(total),0) t FROM orders WHERE strftime('%Y-%m',created_at)=strftime('%Y-%m','now')", one=True
    )
    open_orders = query_db("SELECT COUNT(*) c FROM orders WHERE kitchen_status IN ('novo','preparo','pronto')", one=True)
    low_stock = query_db("SELECT COUNT(*) c FROM ingredients WHERE stock_current <= stock_min", one=True)
    chart_rows = query_db(
        "SELECT date(created_at) d, COALESCE(SUM(total),0) t FROM orders WHERE created_at >= date('now','-7 day') GROUP BY date(created_at)"
    )
    return render_template(
        "dashboard.html",
        daily_sales=daily_sales,
        month_sales=month_sales,
        open_orders=open_orders,
        low_stock=low_stock,
        chart_rows=chart_rows,
    )


@app.route("/pdv")
@login_required
@role_required("Admin", "Caixa")
def pdv():
    products = query_db(
        "SELECT p.*, c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.name"
    )
    clients = query_db("SELECT * FROM clients ORDER BY name")
    return render_template("pdv.html", products=products, clients=clients)


@app.route("/orders/create", methods=["POST"])
@login_required
@role_required("Admin", "Caixa")
def create_order():
    data = request.get_json(silent=True) or {}
    items = data.get("items", [])
    if not items:
        return jsonify({"ok": False, "error": "Pedido sem itens."}), 400

    payment_method = data.get("payment_method", "Dinheiro")
    client_id = data.get("client_id")
    if payment_method == "Fiado" and not client_id:
        return jsonify({"ok": False, "error": "Fiado exige cliente."}), 400

    subtotal = sum(float(it.get("price", 0)) * int(it.get("qty", 1)) for it in items)
    discount_type = data.get("discount_type", "R$")
    discount_value = float(data.get("discount_value", 0) or 0)
    service_fee = float(data.get("service_fee", 0) or 0)
    discount_amount = (subtotal * (discount_value / 100.0)) if discount_type == "%" else discount_value
    total = max(subtotal - discount_amount + service_fee, 0)

    order_id = execute_db(
        """
        INSERT INTO orders
        (created_at, origin, order_type, client_id, status, kitchen_status, payment_method, subtotal, discount_type, discount_value, service_fee, total, notes, cash_received, change_value, delivery_fee)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        """,
        (
            datetime.now().isoformat(timespec="seconds"),
            data.get("origin", "balcao"),
            data.get("order_type", "retirada"),
            client_id,
            "aberto",
            "novo",
            payment_method,
            subtotal,
            discount_type,
            discount_value,
            service_fee,
            total,
            data.get("notes", ""),
            float(data.get("cash_received", 0) or 0),
            float(data.get("change_value", 0) or 0),
            float(data.get("delivery_fee", 0) or 0),
        ),
    )

    for it in items:
        execute_db(
            "INSERT INTO order_items (order_id, product_id, product_name, qty, unit_price, total_price, notes) VALUES (?,?,?,?,?,?,?)",
            (
                order_id,
                it.get("product_id"),
                it.get("name"),
                int(it.get("qty", 1)),
                float(it.get("price", 0)),
                float(it.get("price", 0)) * int(it.get("qty", 1)),
                it.get("notes", ""),
            ),
        )
        recipes = query_db("SELECT ingredient_id, qty FROM product_recipes WHERE product_id=?", (it.get("product_id"),))
        for r in recipes:
            execute_db(
                "UPDATE ingredients SET stock_current=stock_current-? WHERE id=?",
                (float(r["qty"]) * int(it.get("qty", 1)), r["ingredient_id"]),
            )
            execute_db(
                "INSERT INTO stock_movements (ingredient_id, movement_type, qty, note, created_at) VALUES (?,?,?,?,?)",
                (
                    r["ingredient_id"],
                    "saida",
                    float(r["qty"]) * int(it.get("qty", 1)),
                    f"Venda pedido #{order_id}",
                    datetime.now().isoformat(timespec="seconds"),
                ),
            )

    audit("CREATE", "PDV", f"Pedido #{order_id} criado")
    return jsonify({"ok": True, "order_id": order_id})


@app.route("/kitchen")
@login_required
def kitchen():
    orders = query_db(
        "SELECT o.*, c.name client_name FROM orders o LEFT JOIN clients c ON c.id=o.client_id WHERE status='aberto' ORDER BY o.id DESC"
    )
    grouped_items = {}
    for o in orders:
        grouped_items[o["id"]] = query_db("SELECT * FROM order_items WHERE order_id=?", (o["id"],))
    return render_template("kitchen.html", orders=orders, grouped_items=grouped_items)


@app.route("/orders/<int:order_id>/kitchen_status", methods=["POST"])
@login_required
def update_kitchen_status(order_id):
    new_status = request.form.get("status")
    if new_status not in ["novo", "preparo", "pronto", "entregue"]:
        flash("Status inválido", "error")
        return redirect(url_for("kitchen"))
    execute_db("UPDATE orders SET kitchen_status=? WHERE id=?", (new_status, order_id))
    if new_status == "entregue":
        execute_db("UPDATE orders SET status='fechado' WHERE id=?", (order_id,))
    audit("UPDATE", "COZINHA", f"Pedido #{order_id} -> {new_status}")
    return redirect(url_for("kitchen"))


@app.route("/delivery")
@login_required
def delivery():
    neighborhoods = query_db("SELECT * FROM neighborhoods ORDER BY name")
    riders = query_db("SELECT * FROM riders WHERE active=1 ORDER BY name")
    orders = query_db(
        "SELECT o.*, c.name client_name, n.name neighborhood_name, r.name rider_name FROM orders o LEFT JOIN clients c ON c.id=o.client_id LEFT JOIN neighborhoods n ON n.id=o.neighborhood_id LEFT JOIN riders r ON r.id=o.rider_id WHERE o.order_type='delivery' ORDER BY o.id DESC"
    )
    return render_template("delivery.html", neighborhoods=neighborhoods, riders=riders, orders=orders)


@app.route("/delivery/status/<int:order_id>", methods=["POST"])
@login_required
def delivery_status(order_id):
    new_status = request.form.get("delivery_status")
    rider_id = request.form.get("rider_id")
    if new_status not in ["aguardando", "em_rota", "entregue"]:
        flash("Status inválido", "error")
    else:
        execute_db("UPDATE orders SET delivery_status=?, rider_id=COALESCE(?, rider_id) WHERE id=?", (new_status, rider_id or None, order_id))
        audit("UPDATE", "DELIVERY", f"Pedido #{order_id}: {new_status}")
    return redirect(url_for("delivery"))


@app.route("/cadastros")
@login_required
def cadastros():
    return render_template(
        "cadastros.html",
        categories=query_db("SELECT * FROM categories ORDER BY name"),
        products=query_db("SELECT p.*, c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC"),
        clients=query_db("SELECT * FROM clients ORDER BY id DESC"),
        riders=query_db("SELECT * FROM riders ORDER BY id DESC"),
        users=query_db("SELECT id,name,username,role,active FROM users ORDER BY id DESC"),
    )


@app.route("/cadastros/<entity>", methods=["POST"])
@login_required
@role_required("Admin", "Caixa")
def save_entity(entity):
    form = request.form
    if entity == "categoria":
        execute_db("INSERT INTO categories (name) VALUES (?)", (form.get("name"),))
    elif entity == "produto":
        execute_db(
            "INSERT INTO products (name, category_id, price, cost, active, prep_time, image_url) VALUES (?,?,?,?,?,?,?)",
            (
                form.get("name"),
                form.get("category_id"),
                form.get("price"),
                form.get("cost"),
                1 if form.get("active") == "on" else 0,
                form.get("prep_time") or 0,
                form.get("image_url") or "",
            ),
        )
    elif entity == "cliente":
        execute_db(
            "INSERT INTO clients (name, phone, address, notes, credit_limit) VALUES (?,?,?,?,?)",
            (form.get("name"), form.get("phone"), form.get("address"), form.get("notes"), form.get("credit_limit") or 0),
        )
    elif entity == "entregador":
        execute_db("INSERT INTO riders (name, phone, active) VALUES (?,?,1)", (form.get("name"), form.get("phone")))
    elif entity == "usuario":
        execute_db(
            "INSERT INTO users (name, username, password_hash, role, active) VALUES (?,?,?,?,1)",
            (form.get("name"), form.get("username"), generate_password_hash(form.get("password")), form.get("role")),
        )
    else:
        flash("Entidade inválida", "error")
        return redirect(url_for("cadastros"))
    audit("CREATE", "CADASTROS", entity)
    flash("Cadastro salvo com sucesso.", "success")
    return redirect(url_for("cadastros"))


@app.route("/estoque")
@login_required
def estoque():
    ingredients = query_db("SELECT * FROM ingredients ORDER BY name")
    movements = query_db("SELECT m.*, i.name ingredient_name FROM stock_movements m LEFT JOIN ingredients i ON i.id=m.ingredient_id ORDER BY m.id DESC LIMIT 80")
    products = query_db("SELECT id,name FROM products ORDER BY name")
    recipe_rows = query_db(
        "SELECT pr.*, p.name product_name, i.name ingredient_name FROM product_recipes pr JOIN products p ON p.id=pr.product_id JOIN ingredients i ON i.id=pr.ingredient_id ORDER BY p.name"
    )
    return render_template("estoque.html", ingredients=ingredients, movements=movements, products=products, recipe_rows=recipe_rows)


@app.route("/estoque/entrada", methods=["POST"])
@login_required
def estoque_entrada():
    ingredient_id = request.form.get("ingredient_id")
    qty = float(request.form.get("qty") or 0)
    note = request.form.get("note", "Entrada")
    if qty <= 0:
        flash("Quantidade deve ser maior que zero.", "error")
        return redirect(url_for("estoque"))
    execute_db("UPDATE ingredients SET stock_current=stock_current+? WHERE id=?", (qty, ingredient_id))
    execute_db(
        "INSERT INTO stock_movements (ingredient_id, movement_type, qty, note, created_at) VALUES (?,?,?,?,?)",
        (ingredient_id, "entrada", qty, note, datetime.now().isoformat(timespec="seconds")),
    )
    audit("UPDATE", "ESTOQUE", f"Entrada insumo {ingredient_id}")
    flash("Entrada registrada.", "success")
    return redirect(url_for("estoque"))


@app.route("/financeiro")
@login_required
def financeiro():
    cash = query_db("SELECT * FROM cash_register ORDER BY id DESC LIMIT 1", one=True)
    by_payment = query_db("SELECT payment_method, COALESCE(SUM(total),0) total FROM orders GROUP BY payment_method")
    expenses = query_db("SELECT * FROM expenses ORDER BY id DESC LIMIT 50")
    fiado = query_db("SELECT o.id, c.name client_name, o.total, o.created_at FROM orders o JOIN clients c ON c.id=o.client_id WHERE o.payment_method='Fiado' AND o.status='aberto'")
    lucro = query_db(
        "SELECT COALESCE(SUM(o.total),0) vendas, COALESCE(SUM(oi.qty*p.cost),0) custos FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id LEFT JOIN products p ON p.id=oi.product_id",
        one=True,
    )
    total_expenses = query_db("SELECT COALESCE(SUM(amount),0) t FROM expenses", one=True)
    lucro_estimado = float(lucro["vendas"] or 0) - float(lucro["custos"] or 0) - float(total_expenses["t"] or 0)
    return render_template("financeiro.html", cash=cash, by_payment=by_payment, expenses=expenses, fiado=fiado, lucro=lucro_estimado)


@app.route("/financeiro/cash", methods=["POST"])
@login_required
def financeiro_cash():
    action = request.form.get("action")
    amount = float(request.form.get("amount") or 0)
    if action == "abrir":
        execute_db(
            "INSERT INTO cash_register (opened_at, opening_amount, status) VALUES (?,?, 'aberto')",
            (datetime.now().isoformat(timespec="seconds"), amount),
        )
    elif action == "fechar":
        execute_db(
            "UPDATE cash_register SET closed_at=?, closing_amount=?, status='fechado' WHERE id=(SELECT id FROM cash_register WHERE status='aberto' ORDER BY id DESC LIMIT 1)",
            (datetime.now().isoformat(timespec="seconds"), amount),
        )
    elif action in ["sangria", "suprimento"]:
        execute_db(
            "INSERT INTO cash_movements (movement_type, amount, note, created_at) VALUES (?,?,?,?)",
            (action, amount, request.form.get("note", ""), datetime.now().isoformat(timespec="seconds")),
        )
    audit("UPDATE", "FINANCEIRO", action)
    return redirect(url_for("financeiro"))


@app.route("/financeiro/despesa", methods=["POST"])
@login_required
def financeiro_despesa():
    execute_db(
        "INSERT INTO expenses (description, amount, created_at) VALUES (?,?,?)",
        (request.form.get("description"), request.form.get("amount"), datetime.now().isoformat(timespec="seconds")),
    )
    audit("CREATE", "FINANCEIRO", "Despesa")
    return redirect(url_for("financeiro"))


@app.route("/relatorios")
@login_required
def relatorios():
    sales = query_db("SELECT date(created_at) d, COALESCE(SUM(total),0) total, COUNT(*) pedidos FROM orders GROUP BY date(created_at) ORDER BY d DESC LIMIT 30")
    top_products = query_db("SELECT product_name, SUM(qty) q FROM order_items GROUP BY product_name ORDER BY q DESC LIMIT 10")
    avg_ticket = query_db("SELECT COALESCE(AVG(total),0) t FROM orders", one=True)
    delivery_by_neighborhood = query_db(
        "SELECT n.name, COUNT(o.id) pedidos, COALESCE(SUM(o.delivery_fee),0) taxas FROM orders o LEFT JOIN neighborhoods n ON n.id=o.neighborhood_id WHERE o.order_type='delivery' GROUP BY n.name"
    )
    low_stock = query_db("SELECT * FROM ingredients WHERE stock_current <= stock_min ORDER BY stock_current")
    return render_template(
        "relatorios.html",
        sales=sales,
        top_products=top_products,
        avg_ticket=avg_ticket,
        delivery_by_neighborhood=delivery_by_neighborhood,
        low_stock=low_stock,
    )


@app.route("/relatorios/export/<report>")
@login_required
def export_csv(report):
    output = io.StringIO()
    writer = csv.writer(output)
    if report == "vendas":
        writer.writerow(["data", "total", "pedidos"])
        for r in query_db("SELECT date(created_at), SUM(total), COUNT(*) FROM orders GROUP BY date(created_at)"):
            writer.writerow(r)
    elif report == "estoque":
        writer.writerow(["insumo", "estoque", "minimo"])
        for r in query_db("SELECT name, stock_current, stock_min FROM ingredients"):
            writer.writerow(r)
    else:
        return "Relatório inválido", 400
    mem = io.BytesIO(output.getvalue().encode("utf-8"))
    mem.seek(0)
    return send_file(mem, mimetype="text/csv", as_attachment=True, download_name=f"{report}.csv")


@app.route("/configuracoes", methods=["GET", "POST"])
@login_required
@role_required("Admin")
def configuracoes():
    if request.method == "POST":
        for key in ["store_name", "city", "whatsapp", "theme_primary", "theme_secondary", "theme_light", "printer_type"]:
            execute_db(
                "INSERT INTO settings (key, value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value",
                (key, request.form.get(key, "")),
            )
        audit("UPDATE", "CONFIG", "Configurações alteradas")
        flash("Configurações salvas.", "success")
        return redirect(url_for("configuracoes"))
    return render_template("configuracoes.html")


@app.route("/auditoria")
@login_required
@role_required("Admin")
def auditoria():
    logs = query_db("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 200")
    return render_template("auditoria.html", logs=logs)


if __name__ == "__main__":
    init_db()
    app.run(host="0.0.0.0", port=8000, debug=True)
