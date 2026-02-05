#!/usr/bin/env python3
"""
MySQL to PostgreSQL Data Migration Script for AutoDB.

This script migrates data from an existing MySQL AutoDB database to PostgreSQL.
It handles table structure differences and data type conversions.

Usage:
    python migrate_mysql_to_postgres.py --mysql-url "mysql://user:pass@host/db" \
                                        --postgres-url "postgresql://user:pass@host/db"

Requirements:
    pip install pymysql psycopg2-binary
"""

import argparse
import sys
from datetime import datetime

import pymysql
import psycopg2
from psycopg2.extras import execute_values


def parse_args():
    """Parse command line arguments."""
    parser = argparse.ArgumentParser(description="Migrate AutoDB data from MySQL to PostgreSQL")
    parser.add_argument("--mysql-host", required=True, help="MySQL host")
    parser.add_argument("--mysql-port", type=int, default=3306, help="MySQL port")
    parser.add_argument("--mysql-user", required=True, help="MySQL user")
    parser.add_argument("--mysql-password", required=True, help="MySQL password")
    parser.add_argument("--mysql-database", required=True, help="MySQL database")
    parser.add_argument("--postgres-host", required=True, help="PostgreSQL host")
    parser.add_argument("--postgres-port", type=int, default=5432, help="PostgreSQL port")
    parser.add_argument("--postgres-user", required=True, help="PostgreSQL user")
    parser.add_argument("--postgres-password", required=True, help="PostgreSQL password")
    parser.add_argument("--postgres-database", required=True, help="PostgreSQL database")
    parser.add_argument("--dry-run", action="store_true", help="Print SQL without executing")
    return parser.parse_args()


def connect_mysql(args):
    """Connect to MySQL database."""
    return pymysql.connect(
        host=args.mysql_host,
        port=args.mysql_port,
        user=args.mysql_user,
        password=args.mysql_password,
        database=args.mysql_database,
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
    )


def connect_postgres(args):
    """Connect to PostgreSQL database."""
    return psycopg2.connect(
        host=args.postgres_host,
        port=args.postgres_port,
        user=args.postgres_user,
        password=args.postgres_password,
        database=args.postgres_database,
    )


def migrate_autodb_rules(mysql_conn, pg_conn, dry_run=False):
    """Migrate autodb_rules table."""
    print("Migrating autodb_rules...")

    with mysql_conn.cursor() as cursor:
        cursor.execute("SELECT * FROM autodb_rules")
        rows = cursor.fetchall()

    if not rows:
        print("  No rules to migrate")
        return

    insert_sql = """
        INSERT INTO autodb.autodb_rules
        (adb_t1, adb_t1_relcol, adb_t2, adb_t2_relcol, adb_t2_dspcol,
         adb_t2_remhost, adb_t2_remuser, adb_t2_rempass)
        VALUES %s
        ON CONFLICT (adb_t1, adb_t1_relcol) DO UPDATE SET
            adb_t2 = EXCLUDED.adb_t2,
            adb_t2_relcol = EXCLUDED.adb_t2_relcol,
            adb_t2_dspcol = EXCLUDED.adb_t2_dspcol,
            adb_t2_remhost = EXCLUDED.adb_t2_remhost,
            adb_t2_remuser = EXCLUDED.adb_t2_remuser,
            adb_t2_rempass = EXCLUDED.adb_t2_rempass
    """

    values = [
        (
            row["adb_t1"],
            row["adb_t1_relcol"],
            row["adb_t2"],
            row["adb_t2_relcol"],
            row["adb_t2_dspcol"],
            row.get("adb_t2_remhost"),
            row.get("adb_t2_remuser"),
            row.get("adb_t2_rempass"),
        )
        for row in rows
    ]

    if dry_run:
        print(f"  Would insert {len(values)} rules")
        return

    with pg_conn.cursor() as cursor:
        execute_values(cursor, insert_sql, values)
    pg_conn.commit()
    print(f"  Migrated {len(values)} rules")


def migrate_autodb_prefs(mysql_conn, pg_conn, dry_run=False):
    """Migrate autodb_prefs table."""
    print("Migrating autodb_prefs...")

    with mysql_conn.cursor() as cursor:
        cursor.execute("SELECT * FROM autodb_prefs")
        rows = cursor.fetchall()

    if not rows:
        print("  No preferences to migrate")
        return

    insert_sql = """
        INSERT INTO autodb.autodb_prefs (dbtable, var, value, "user")
        VALUES %s
    """

    values = [
        (row["dbtable"], row["var"], row["value"], row.get("user", ""))
        for row in rows
    ]

    if dry_run:
        print(f"  Would insert {len(values)} preferences")
        return

    with pg_conn.cursor() as cursor:
        # Clear existing prefs
        cursor.execute("TRUNCATE autodb.autodb_prefs RESTART IDENTITY")
        execute_values(cursor, insert_sql, values)
    pg_conn.commit()
    print(f"  Migrated {len(values)} preferences")


def get_mysql_tables(mysql_conn, database):
    """Get list of user tables from MySQL database."""
    with mysql_conn.cursor() as cursor:
        cursor.execute(
            """
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = %s
              AND table_type = 'BASE TABLE'
              AND table_name NOT IN ('autodb_rules', 'autodb_prefs')
            """,
            (database,),
        )
        return [row["table_name"] for row in cursor.fetchall()]


def get_table_columns(mysql_conn, database, table):
    """Get column information for a table."""
    with mysql_conn.cursor() as cursor:
        cursor.execute(
            """
            SELECT column_name, data_type, is_nullable, column_key, extra
            FROM information_schema.columns
            WHERE table_schema = %s AND table_name = %s
            ORDER BY ordinal_position
            """,
            (database, table),
        )
        return cursor.fetchall()


def migrate_user_table(mysql_conn, pg_conn, database, table, dry_run=False):
    """Migrate a user data table."""
    print(f"Migrating {database}.{table}...")

    # Get columns
    columns = get_table_columns(mysql_conn, database, table)
    column_names = [c["column_name"] for c in columns]

    # Fetch data
    with mysql_conn.cursor() as cursor:
        cursor.execute(f"SELECT * FROM `{table}`")
        rows = cursor.fetchall()

    if not rows:
        print(f"  No data in {table}")
        return

    # Build insert statement
    cols_quoted = ", ".join(f'"{c}"' for c in column_names)
    insert_sql = f"""
        INSERT INTO autodb.{table} ({cols_quoted})
        VALUES %s
        ON CONFLICT DO NOTHING
    """

    values = [tuple(row.get(c) for c in column_names) for row in rows]

    if dry_run:
        print(f"  Would insert {len(values)} rows into {table}")
        return

    with pg_conn.cursor() as cursor:
        execute_values(cursor, insert_sql, values)
    pg_conn.commit()
    print(f"  Migrated {len(values)} rows")


def main():
    """Main migration function."""
    args = parse_args()

    print(f"Starting migration at {datetime.now()}")
    print(f"MySQL: {args.mysql_host}/{args.mysql_database}")
    print(f"PostgreSQL: {args.postgres_host}/{args.postgres_database}")
    print()

    if args.dry_run:
        print("DRY RUN - no changes will be made")
        print()

    # Connect to databases
    mysql_conn = connect_mysql(args)
    pg_conn = connect_postgres(args)

    try:
        # Migrate metadata tables
        migrate_autodb_rules(mysql_conn, pg_conn, args.dry_run)
        migrate_autodb_prefs(mysql_conn, pg_conn, args.dry_run)

        # Get and migrate user tables
        tables = get_mysql_tables(mysql_conn, args.mysql_database)
        print(f"\nFound {len(tables)} user tables to migrate")

        for table in tables:
            try:
                migrate_user_table(mysql_conn, pg_conn, args.mysql_database, table, args.dry_run)
            except Exception as e:
                print(f"  ERROR migrating {table}: {e}")
                continue

        print(f"\nMigration completed at {datetime.now()}")

    finally:
        mysql_conn.close()
        pg_conn.close()


if __name__ == "__main__":
    main()
