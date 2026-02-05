"""Authentication routes for Azure AD OAuth2."""

import secrets

from fastapi import APIRouter, Depends, Request
from fastapi.responses import HTMLResponse, RedirectResponse
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.auth.azure_ad import AzureADAuth
from autodb.auth.middleware import AuthMiddleware
from autodb.config import get_settings
from autodb.db import get_db
from autodb.dependencies import get_templates
from autodb.services import Role, RoleService

router = APIRouter(prefix="/auth", tags=["auth"])


@router.get("/login")
async def login(request: Request) -> RedirectResponse:
    """Initiate Azure AD login flow."""
    settings = get_settings()

    if not settings.auth_enabled:
        return RedirectResponse(url="/", status_code=302)

    auth = AzureADAuth()

    # Generate state for CSRF protection
    state = secrets.token_urlsafe(32)
    auth_url = auth.get_auth_url(state=state)

    response = RedirectResponse(url=auth_url, status_code=302)
    # Store state in cookie for verification
    response.set_cookie("auth_state", state, httponly=True, max_age=600)
    return response


@router.get("/callback")
async def callback(
    request: Request,
    code: str | None = None,
    state: str | None = None,
    error: str | None = None,
    db: AsyncSession = Depends(get_db),
) -> RedirectResponse:
    """Handle Azure AD OAuth2 callback."""
    settings = get_settings()
    templates = get_templates()

    if error:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": f"Authentication error: {error}"},
        )

    if not code:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "No authorization code received"},
        )

    # Verify state
    stored_state = request.cookies.get("auth_state")
    if state != stored_state:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "Invalid state parameter"},
        )

    auth = AzureADAuth()
    token_response = await auth.get_token_from_code(code)

    if not token_response:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "Failed to acquire token"},
        )

    user = auth.parse_id_token(token_response)
    if not user:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "Failed to parse user information"},
        )

    # Look up user's role
    role_service = RoleService(db)

    # Bootstrap: if no admins exist, make this user an admin
    if not await role_service.has_any_admin():
        await role_service.set_role(user.email, Role.ADMIN)
        await db.commit()

    role = await role_service.get_role(user.email)
    user.role = role.value

    # Create session
    middleware = AuthMiddleware(None, settings)
    session_cookie = middleware.create_session_cookie(user)

    response = RedirectResponse(url="/", status_code=302)
    response.set_cookie(
        settings.session_cookie_name,
        session_cookie,
        httponly=True,
        max_age=604800,  # 7 days
        samesite="lax",
    )
    # Clear auth state cookie
    response.delete_cookie("auth_state")
    return response


@router.get("/logout")
async def logout(request: Request) -> RedirectResponse:
    """Log out user and optionally redirect to Azure AD logout."""
    settings = get_settings()

    response = RedirectResponse(url="/", status_code=302)
    response.delete_cookie(settings.session_cookie_name)

    if settings.auth_enabled:
        auth = AzureADAuth()
        # Get the base URL for post-logout redirect
        base_url = str(request.base_url).rstrip("/")
        logout_url = auth.get_logout_url(post_logout_redirect_uri=base_url)
        response = RedirectResponse(url=logout_url, status_code=302)
        response.delete_cookie(settings.session_cookie_name)

    return response


@router.get("/status", response_class=HTMLResponse)
async def auth_status(request: Request) -> HTMLResponse:
    """Get authentication status banner HTML."""
    templates = get_templates()
    settings = get_settings()

    from autodb.auth import get_current_user

    user = get_current_user(request)

    return templates.TemplateResponse(
        request,
        "partials/auth_banner.html",
        {"user": user, "auth_enabled": settings.auth_enabled},
    )
