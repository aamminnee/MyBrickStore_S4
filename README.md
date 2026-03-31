# 🧱 MyBrickStore - SAE S3

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)
![Java](https://img.shields.io/badge/Java-17-ED8B00?logo=openjdk)
![MariaDB](https://img.shields.io/badge/MariaDB-10.6-003545?logo=mariadb)
![License](https://img.shields.io/badge/License-MIT-green.svg)

> **From image to brick.**
> MyBrickStore is a complete e-commerce solution that transforms any image into a LEGO® mosaic, allowing users to order the pieces and manage stocks via a connected factory simulation.

---

## 🚀 Quick Access

| Resource | Link | Description |
| :--- | :--- | :--- |
| **📘 Documentation** | [**View Technical Doc**](https://alkzhab.github.io/MyBrickStore-Doc/) | Architecture, Javadoc, PHPDoc, SQL. |
| **🌐 Website** | [https://mybrickstore.duckdns.org](https://mybrickstore.duckdns.org) | Main application (PHP MVC). |
| **📄 Reports** | [See /Rapports folder](/Rapports) | Technical and functional reports (PDF). |
| **📺 Videos** | [See /videos folder](/videos) | Website and Java module demonstrations. |

---

## 🔐 Test Credentials (Cheat Sheet)

To facilitate grading and testing, here are the pre-configured accounts:

### 👨‍💻 Administrator (Back-Office)
Access to the full dashboard (Stock management, orders, statistics, restocking).
* **Login / Email:** `admin`
* **Password:** `123456789aA!`

### 💳 Payment (PayPal Sandbox)
To validate a dummy order during checkout:
* **Email:** `sb-o00un48707050@personal.example.com`
* **Password:** `0oH&XU{K`

---

## ✨ Key Features

### 🎨 User Experience (Front-End)
* **Guest Mode:** Ability to create a mosaic and add to cart without registration (login only required at checkout).
* **Image Processing:** Real-time upload, cropping (Cropper.js), and pixelation.
* **Ergonomics:** Secure forms (password double-confirmation, "show password" button).

### ⚙️ Engine & Algorithms (Backend)
* **MVC Architecture:** Proprietary PHP framework (Router, Controllers, Models).
* **Tiling Algorithms (C/Java):**
    * *Profitability Mode:* Optimized compromise between cost and accuracy.
    * *Freeform Mode:* Greedy algorithm prioritizing large pieces.
* **Intelligent Database:**
    * Security triggers (Immutability of invoices and orders).
    * Stored procedures for real-time stock calculation.

### 🏭 Factory Simulation (Java)
* Production order management.
* Transaction validation via **Proof of Work** (Cryptographic mining).
* Two-way synchronization with the website.

---

## 📚 Quality & Documentation

With a focus on professional standards, the code follows industry best practices. Each module has its automatically generated normative documentation:

| Module | Standard | Tool |
| :--- | :--- | :--- |
| **☕ Java** | Oracle Javadoc | *Javadoc* |
| **🐘 PHP** | PSR-5 / PSR-19 | *phpDocumentor* |
| **⚙️ C** | Doxygen Style | *Doxygen* |
| **🗃️ SQL** | DBML | *DBDocs* |

🚀 **[Access the Documentation Portal](https://alkzhab.github.io/MyBrickStore-Doc/)**

---

## 🛠️ Installation & Setup

### Prerequisites
* Web Server (Apache/Nginx via XAMPP, WAMP, or MAMP).
* PHP >= 8.0 with GD extension enabled.
* MariaDB or MySQL database.
* Java Runtime (JRE 17) for the factory module.

### Procedure
1. **Clone the project** into your server folder (`htdocs` or `www`):
   ```bash
   git clone [https://github.com/aamminnee/SAE_S3_BUT2_INFO.git](https://github.com/aamminnee/SAE_S3_BUT2_INFO.git) MyBrickStore
