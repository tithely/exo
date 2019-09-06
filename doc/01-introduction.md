# Introduction

Exo was designed to facilitate database migrations in environments where a single schema must be 
maintained across an unbounded number of databases. The primary motivation for its development is
multi-tenant applications in which each tenant has a dedicated database that must stay up to date
with application development. The library offers a programmatic means of defining and executing
migrations without making assumptions about application architecture.

## Concepts

*Operations* are the core primitive used by the library and offer an abstract representation of a
change to the database schema. Operations represent the creation, modification and removal of
database tables, columns and indexes. Prior to being transformed into SQL statements, several
operations may be reduced into a single operation in order to avoid redundant database queries.

*Migrations* offer a developer-friendly means of defining changes to the schema and act as
operation factories.

*Histories* are versioned collections of migrations that provide facilities for playing and
rewinding sets of operations.

*Handlers* are used to execute histories against database connections. Specific source and
target versions are used to determine which sets of operations are applied.
