# AMS – Asset Management System

## Overview

AMS is a secure, multi-tenant asset management system designed to manage assets across multiple institutions from a single platform.

It solves real-world challenges such as asset loss, weak accountability, inefficient audits, and poor maintenance planning—without relying on cloud tracking or geolocation.

The system emphasizes security, tenant isolation, and auditability, while integrating predictive analytics for smarter decision-making.

---

## Core Features

- Multi-tenant (institution-aware) architecture
- Role-Based Access Control (RBAC)
  - Super Admin
  - Admin
  - Security
  - ICT
  - Auditor
  - Manager
  - Staff
- Full asset lifecycle management
- Secure check-in / check-out workflows
- Predictive analytics for asset risk and maintenance
- Immutable system-wide audit logs
- Dashboards and reporting

---

## System Architecture

- **Frontend:** React
- **Backend:** PHP (REST APIs)
- **Predictive Analytics:** Python
- **Database:** PostgreSQL

---

## Modules

### 1. Authentication & Tenant Access

Secure login with tenant-aware access control.

### 2. Institution & User Management

Manage institutions, users, and roles with strict isolation.

### 3. Asset Management

Core module for registering and tracking assets.

### 4. Check-In / Check-Out & Movement

Tracks asset movement and ownership.

### 5. Maintenance & Predictive Maintenance

Handles scheduled and predictive maintenance.

### 6. Predictive Analytics

Analyzes asset usage and risk patterns.

### 7. Reporting & Dashboards

Provides insights for decision-making.

### 8. Global Audit Trail

Captures all system activity in immutable logs.

---

## Design Principles

- Security first
- Strong tenant isolation
- Full auditability
- Scalable architecture
- Data integrity

---

## Use Cases

- Universities and colleges
- Corporate IT asset management
- Government institutions
- Organizations requiring strict audit trails
