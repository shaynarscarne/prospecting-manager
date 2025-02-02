# TC Prospecting Database

TC Prospecting Database is a WordPress plugin and demo application that tracks prospecting and mining efforts within a simulated SWC galaxy. It allows users to create and design planets, manage resource deposits, import planet data from an external database, view detailed changelogs of changes, generate prospecting reports, and even upload XML files to batch update deposits.

This project showcases a modular PHP codebase integrated into WordPress along with Docker configuration for a fast, portable demo environment. It’s designed as both a proof-of-concept.

---

## Table of Contents

- [Features](#features)
- [Directory Structure](#directory-structure)
- [Getting Started](#getting-started)
- [Usage](#usage)
- [How It Works](#how-it-works)

---

## Features

- **Planet Creation & Importing**  
  - Create new planets (asteroids) manually via an intuitive UI.
  - Import planet data from an external SWC database.

- **Interactive Planet Designer**  
  - Edit planet meta (name, system, sector, location, size).
  - Modify terrain grids using overlay modals similar to SWC’s interface.
  - Click grid cells to add, modify, and delete deposits (resource entries).

- **Deposit Management & Changelogs**  
  - Manage resource deposits by clicking on grid cells.
  - View changelogs for deposits, grid changes, and planet-level modifications.
  - Update deposits with AJAX actions secured via nonces.

- **XML Upload Support**  
  - Batch update deposit data by uploading XML files.
  - Parse and process XML to add/update/delete deposits accordingly.

- **Reporting**  
  - Generate reports filtering by system, sector, raw materials, or prospector.
  - Display deposit counts, total amounts, and material breakdowns.

---

## Directory Structure

```bash
├── init.sql # Database initialization script
├── Dockerfile # Container build instructions
├── docker-compose.yml # Docker Compose configuration for WordPress + MySQL
├── boot.sh # Boot script for initializing the Docker container
├── prespa/ # Custom WordPress theme used for the demo site
└── tc-prospecting/ # Main plugin folder
├── assets/ # CSS and JavaScript files
│ ├── bootstrap.js
│ ├── bootstrap.css
│ ├── select2.js
│ ├── select2.css
│ ├── prospecting.js
│ ├── planet-designer.js
│ └── style.css
├── classes/ # Core PHP classes
│ ├── class-deposit.php
│ ├── class-grid.php
│ ├── class-planet.php
│ └── class-prospecting-logger.php
├── controllers/ # AJAX controllers and endpoints
│ ├── class-changelog-controller.php
│ ├── class-deposits-controller.php
│ ├── class-planetcontroller.php
│ ├── class-planet-designer-controller.php
│ ├── class-planet-importer-controller.php
│ ├── class-report-controller.php
│ └── class-xml-importer-controller.php
├── views/ # UI views and templates
│ ├── planet-designer-view.php
│ ├── planet-import-view.php
│ └── planet-view.php
└── tc-prospecting.php # Main plugin file
```
---

## Getting Started

### Prerequisites

- [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/)
- Basic knowledge of WordPress development (PHP, JavaScript, and AJAX)

### Installation

1. **Clone the Repository**

git clone https://github.com/<your-username>/tc-prospecting.git
cd tc-prospecting


2. **Build and Start the Containers**

Use Docker Compose to build and start your local WordPress environment:

docker-compose up --build
After a successful build, the terminal will display a link. By default, the WordPress site is accessible at:
http://localhost:8000/prospecting-database

3. **Access the Demo**

Visit the URL in your browser and explore the prospecting dashboard to create, design, and report on planets.

---

## Usage

- **Planet Creation & Importing**  
- Click "Create New Planet" to manually add a planet.
- Use the "Add Planet from External DB" option to import planet data from an external SWC database table.

- **Planet Designer**  
- Edit planet metadata, modify the terrain grid, and update deposit data by clicking on grid cells.
- Use modals (e.g., "Modify Planet", "Save Planet Terrain") to interact with the planet design interface.

- **Deposit and Changelog Management**  
- Click grid cells to add new deposits or modify existing ones.
- View detailed changelogs for each deposit or grid change.

- **Uploading XML Data**  
- Use the XML Upload modal to process batch deposit updates via XML file import.

- **Reporting**  
- Generate prospecting reports (by system, sector, raw materials, or prospector) to see deposit distributions and summary statistics.

---


## How It Works

- **WordPress Integration**  
TC Prospecting is implemented as a WordPress plugin that enqueues custom scripts and styles. It uses shortcodes to insert its dashboard into WordPress pages.

- **AJAX-Powered Interactivity**  
The plugin uses AJAX for interactions such as updating planet info, modifying grid terrain, saving deposit data, and generating reports. All AJAX endpoints are secured via nonces.

- **Modular PHP Architecture**  
The PHP code is organized into classes for planets, grids, deposits, and logging (changelog). Controllers handle incoming AJAX requests while view files render the UI.

- **Docker Environment**  
A Dockerized WordPress and MySQL setup allows you to quickly launch and test the application locally. The provided Dockerfile, docker-compose.yml, and boot.sh scripts streamline the environment setup.