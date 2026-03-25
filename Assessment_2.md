# Assessment 2

## Project Title: Digital Asset Custody Platform

---

## Case Study Overview

You are a software developer tasked with digitalizing and automating the current business process of a precious metals storage company called **Bare Metals Pvt**. Your task is to design and implement a minimal software solution that supports part of the company's operational workflow.

Bare Metals provides physical custody of precious metals for clients across multiple vaults. With their new digital platform, the company aims to modernize how customer accounts, asset deposits, valuations, and storage types are managed. They also plan to expand beyond gold to offer silver and platinum.

Every customer is assigned an account that holds their portfolio of assets. Each metal is valued at the current market price, and every deposit is logged in a database with details such as deposit number, quantity (kg), and owner account ID to ensure traceability and avoid disputes.

The company provides two storage options:

### Unallocated Storage (Retail Clients)

- Metals are stored in a pooled bulk.
- Customers hold a *percentage* of the total pool (not specific bars).
- Quantities are represented in kilograms.

### Allocated Storage (Institutional Clients)

- Each bar is individually tracked by serial number.
- Bars remain legally owned by the specific client.
- Assets are stored separately in the ledger, even if physically located in the same vault.

---

## Requirements

Build a minimal system prototype that supports:

- Customer accounts
- Deposits
- Withdrawals
- Asset valuation
- Two storage models:
  - **Allocated** (bar-level tracking)
  - **Unallocated** (pooled)
- Gold must be supported; system should be designed to extend to silver/platinum.

### Implementation Flexibility

You may deliver the solution in any of the following styles:

- Backend + UI
- Full-stack web application
- Frontend with mocked data and/or APIs

Any programming language or framework is acceptable.

### Data Layer

- SQL database preferred
- In-memory/array-based/JSON/Frontend persistence also acceptable

---

## Deliverables

Your submission must include:

- A working application
- A GitHub repository with **incremental commits**
- A modern UI that allows interacting with key flows
- A simple architecture sketch + data model
- At least **5 edge cases** and how your design handles them

---

## What to Submit

Provide a **GitHub repository link** containing:

- The full source code
- A **README** with:
  - Setup instructions
  - Any assumptions or architecture decisions
  - Example API calls (if applicable)

---

## Notes

- The assignment is open-ended with no single right solution. Implement as much as possible in the given duration.
- We evaluate your **interpretation**, **design quality**, **reasoning**, and **implementation decisions**.
- You will be required to present your solution.
