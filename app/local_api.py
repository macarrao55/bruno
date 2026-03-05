from __future__ import annotations

from fastapi import FastAPI

from app.config import CONFIG
from app.services.product_service import ProductService

api = FastAPI(title="Boldrini Local API", version="1.0")
svc = ProductService()


@api.get("/health")
def health():
    return {"ok": True, "feature_local_api": CONFIG.feature_local_api}


@api.get("/products")
def products(q: str = ""):
    return svc.list_products(q)
