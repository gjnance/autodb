"""API routes."""

from autodb.api.admin import router as admin_router
from autodb.api.auth import router as auth_router
from autodb.api.browser import router as browser_router
from autodb.api.crud import router as crud_router
from autodb.api.relations import router as relations_router
from autodb.api.suggest import router as suggest_router

__all__ = ["browser_router", "crud_router", "suggest_router", "auth_router", "relations_router", "admin_router"]
