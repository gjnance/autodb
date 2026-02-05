"""Role-based access control service."""

from enum import Enum

from sqlalchemy import delete, func, select
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.db.models import UserRole


class Role(str, Enum):
    """User roles with increasing privilege levels."""

    GUEST = "guest"  # Unauthenticated - read-only, no preferences
    VIEWER = "viewer"  # Authenticated - read-only
    EDITOR = "editor"  # Authenticated - read/write
    ADMIN = "admin"  # Authenticated - full access including admin schemas


# Schemas that require admin role to view
ADMIN_ONLY_SCHEMAS = {"autodb", "public", "pg_catalog", "information_schema"}


class RoleService:
    """Service for managing user roles and permissions."""

    def __init__(self, db: AsyncSession):
        self.db = db

    async def get_role(self, email: str | None) -> Role:
        """Get the role for a user by email.

        Returns GUEST for unauthenticated users (None email).
        Returns VIEWER for authenticated users not in the roles table.
        """
        if not email:
            return Role.GUEST

        query = select(UserRole.role).where(
            func.lower(UserRole.email) == email.lower()
        )
        result = await self.db.execute(query)
        role_str = result.scalar_one_or_none()

        if role_str is None:
            return Role.VIEWER

        return Role(role_str)

    async def set_role(self, email: str, role: Role) -> UserRole:
        """Set or update a user's role."""
        # Check if user exists
        query = select(UserRole).where(
            func.lower(UserRole.email) == email.lower()
        )
        result = await self.db.execute(query)
        existing = result.scalar_one_or_none()

        if existing:
            existing.role = role.value
            await self.db.flush()
            return existing
        else:
            user_role = UserRole(email=email.lower(), role=role.value)
            self.db.add(user_role)
            await self.db.flush()
            return user_role

    async def delete_role(self, email: str) -> bool:
        """Remove a user's role assignment (they'll default to viewer)."""
        result = await self.db.execute(
            delete(UserRole).where(
                func.lower(UserRole.email) == email.lower()
            )
        )
        await self.db.flush()
        return result.rowcount > 0

    async def get_all_roles(self) -> list[UserRole]:
        """Get all role assignments."""
        query = select(UserRole).order_by(UserRole.email)
        result = await self.db.execute(query)
        return list(result.scalars().all())

    async def has_any_admin(self) -> bool:
        """Check if any admin users exist."""
        query = select(func.count()).select_from(UserRole).where(
            UserRole.role == Role.ADMIN.value
        )
        result = await self.db.execute(query)
        count = result.scalar()
        return count > 0

    def can_write(self, role: Role) -> bool:
        """Check if role has write permission."""
        return role in (Role.EDITOR, Role.ADMIN)

    def can_view_schema(self, role: Role, schema: str) -> bool:
        """Check if role can view a schema."""
        if schema in ADMIN_ONLY_SCHEMAS:
            return role == Role.ADMIN
        return True

    def filter_schemas(self, role: Role, schemas: list[str]) -> list[str]:
        """Filter schemas based on role permissions."""
        return [s for s in schemas if self.can_view_schema(role, s)]
