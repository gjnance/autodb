"""Admin routes for user role management."""

from fastapi import APIRouter, Depends, HTTPException, Request
from fastapi.responses import HTMLResponse
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.auth import get_current_user
from autodb.auth.azure_ad import User
from autodb.db import get_db
from autodb.dependencies import get_templates
from autodb.services import Role, RoleService

router = APIRouter(prefix="/admin", tags=["admin"])


def _check_admin(user: User | None) -> None:
    """Check if user is admin. Raises HTTPException if not."""
    if not user:
        raise HTTPException(status_code=401, detail="Authentication required")
    if Role(user.role) != Role.ADMIN:
        raise HTTPException(status_code=403, detail="Admin access required")


@router.get("", response_class=HTMLResponse)
async def admin_page(
    request: Request,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Admin page for user role management."""
    _check_admin(user)
    templates = get_templates()

    role_service = RoleService(db)
    user_roles = await role_service.get_all_roles()

    return templates.TemplateResponse(
        request,
        "admin.html",
        {
            "user_roles": user_roles,
            "roles": [Role.VIEWER, Role.EDITOR, Role.ADMIN],
            "current_user": user,
        },
    )


@router.post("/roles", response_class=HTMLResponse)
async def add_role(
    request: Request,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Add or update a user role."""
    _check_admin(user)
    templates = get_templates()

    form_data = await request.form()
    email = form_data.get("email", "").strip().lower()
    role_str = form_data.get("role", "viewer")

    if not email:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "Email is required"},
        )

    try:
        role = Role(role_str)
    except ValueError:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": f"Invalid role: {role_str}"},
        )

    role_service = RoleService(db)
    await role_service.set_role(email, role)
    await db.commit()

    return templates.TemplateResponse(
        request,
        "partials/success.html",
        {"message": f"Role '{role.value}' assigned to {email}"},
    )


@router.delete("/roles/{email}", response_class=HTMLResponse)
async def delete_role(
    request: Request,
    email: str,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Delete a user role (they'll default to viewer)."""
    _check_admin(user)
    templates = get_templates()

    # Don't allow deleting own role
    if user and user.email.lower() == email.lower():
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "Cannot remove your own admin role"},
        )

    role_service = RoleService(db)
    deleted = await role_service.delete_role(email)
    await db.commit()

    if deleted:
        return templates.TemplateResponse(
            request,
            "partials/success.html",
            {"message": f"Role removed for {email} (will default to viewer)"},
        )
    else:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "User not found"},
        )
