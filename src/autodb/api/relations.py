"""Relation rules management routes."""

from fastapi import APIRouter, Depends, HTTPException, Request
from fastapi.responses import HTMLResponse
from sqlalchemy import delete, select
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.auth import get_current_user
from autodb.auth.azure_ad import User
from autodb.db import get_db
from autodb.db.models import Rule
from autodb.dependencies import get_templates
from autodb.services import Role

router = APIRouter(tags=["relations"])


def _check_admin_permission(user: User | None) -> None:
    """Check if user has admin permission. Raises HTTPException if not."""
    if not user:
        raise HTTPException(status_code=401, detail="Authentication required")

    if Role(user.role) != Role.ADMIN:
        raise HTTPException(status_code=403, detail="Admin permission required")


@router.get("/relations", response_class=HTMLResponse)
async def relations_page(
    request: Request,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Get the relations management page."""
    _check_admin_permission(user)
    templates = get_templates()

    # Fetch all existing rules
    result = await db.execute(
        select(Rule).order_by(Rule.schema_name, Rule.table_name, Rule.column_name)
    )
    rules = result.scalars().all()

    return templates.TemplateResponse(
        request,
        "relations.html",
        {"rules": rules},
    )


@router.get("/relations/new", response_class=HTMLResponse)
async def new_relation_form(
    request: Request,
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Get the form for creating a new relation (step 1)."""
    _check_admin_permission(user)
    templates = get_templates()
    return templates.TemplateResponse(
        request,
        "partials/relation_form_step1.html",
        {"rule": None},
    )


@router.post("/relations/step2", response_class=HTMLResponse)
async def relation_form_step2(
    request: Request,
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Get step 2 of the relation form after source is selected."""
    _check_admin_permission(user)
    templates = get_templates()
    form_data = await request.form()

    source_schema = form_data.get("source_schema", "")
    source_table = form_data.get("source_table", "")
    source_column = form_data.get("source_column", "")

    return templates.TemplateResponse(
        request,
        "partials/relation_form_step2.html",
        {
            "source_schema": source_schema,
            "source_table": source_table,
            "source_column": source_column,
        },
    )


@router.post("/relations", response_class=HTMLResponse)
async def create_relation(
    request: Request,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Create a new relation rule."""
    _check_admin_permission(user)
    templates = get_templates()
    form_data = await request.form()

    source_schema = form_data.get("source_schema", "")
    source_table = form_data.get("source_table", "")
    source_column = form_data.get("source_column", "")
    target_schema = form_data.get("target_schema", "")
    target_table = form_data.get("target_table", "")
    target_column = form_data.get("target_column", "")
    display_column = form_data.get("display_column", "")

    # Validate required fields
    if not all([source_schema, source_table, source_column,
                target_schema, target_table, target_column, display_column]):
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "All fields are required"},
        )

    rule = Rule(
        schema_name=source_schema,
        table_name=source_table,
        column_name=source_column,
        map_schema=target_schema,
        map_table=target_table,
        map_column=target_column,
        map_display=display_column,
    )

    db.add(rule)
    try:
        await db.commit()
    except Exception as e:
        await db.rollback()
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": f"Failed to create rule: {e}"},
        )

    return templates.TemplateResponse(
        request,
        "partials/success.html",
        {"message": "Relation rule created successfully"},
    )


@router.delete("/relations/{rule_id}", response_class=HTMLResponse)
async def delete_relation(
    request: Request,
    rule_id: int,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Delete a relation rule."""
    _check_admin_permission(user)
    templates = get_templates()

    result = await db.execute(
        delete(Rule).where(Rule.id == rule_id)
    )
    await db.commit()

    if result.rowcount > 0:
        return templates.TemplateResponse(
            request,
            "partials/success.html",
            {"message": "Relation rule deleted successfully"},
        )
    else:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "Rule not found"},
        )
