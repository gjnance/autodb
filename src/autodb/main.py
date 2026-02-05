"""FastAPI application entry point."""

from contextlib import asynccontextmanager
from pathlib import Path

from fastapi import Depends, FastAPI, Request
from fastapi.responses import HTMLResponse
from fastapi.staticfiles import StaticFiles

from autodb.api import admin_router, auth_router, browser_router, crud_router, relations_router, suggest_router
from autodb.auth import get_current_user
from autodb.auth.azure_ad import User
from autodb.auth.middleware import AuthMiddleware
from autodb.config import get_settings
from autodb.db.database import engine
from autodb.dependencies import get_templates


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Application lifespan handler."""
    yield
    await engine.dispose()


settings = get_settings()

app = FastAPI(
    title=settings.app_title,
    version="2.0.0",
    lifespan=lifespan,
)

# Add authentication middleware
app.add_middleware(AuthMiddleware)

# Mount static files
static_dir = Path(__file__).parent / "static"
if static_dir.exists():
    app.mount("/static", StaticFiles(directory=str(static_dir)), name="static")

# Include routers
app.include_router(browser_router)
app.include_router(crud_router)
app.include_router(suggest_router)
app.include_router(auth_router)
app.include_router(relations_router)
app.include_router(admin_router)


@app.get("/", response_class=HTMLResponse)
async def index(
    request: Request,
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Main application page."""
    templates = get_templates()
    return templates.TemplateResponse(
        request,
        "base.html",
        {"user": user},
    )


@app.get("/health")
async def health_check() -> dict[str, str]:
    """Health check endpoint for container orchestration."""
    return {"status": "healthy"}
