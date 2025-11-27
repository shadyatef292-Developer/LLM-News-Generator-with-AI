# ⚡ LLM News Studio Pro

**Enterprise-Grade Content Engine for WordPress.** A fully automated solution leveraging **Groq** for extreme speed and **Unsplash** for legally attributed, high-quality featured images.

[![WordPress Version](https://img.shields.io/badge/WordPress-5.8+-blue?logo=wordpress)](https://www.wordpress.org)
[![License](https://img.shields.io/badge/License-GPLv2-red)](LICENSE.md)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-success)]()

---

## ✨ Core Features & Architecture

This plugin was developed adhering to professional WordPress and PHP standards, built using a robust **Object-Oriented Programming (OOP)** architecture.

* **Groq-Powered Speed:** Leverages Groq's low-latency inference engine (`llama-3.1-8b-instant`) for articles generated in seconds, perfect for high-volume content demands.
* **Secure Image Integration:** Automatically searches Unsplash for featured images, handles the download robustly (`download_url`), and inserts necessary legal attribution credits.
* **SEO-First Output:** Content is structured with strong headings (`H2`) and rich media (lists), while meta-data (Tags, Excerpts) is automatically populated for maximum search visibility.
* **Professional Structure:** Code is divided into dedicated classes (`Generator`, `Settings`, `Assets`) for maintainability, security, and easy debugging.
* **Security Focus:** Features Nonce checks, capability restrictions, and strict input/output sanitization.

## 🚀 Installation & Setup

1.  Clone the repository into your WordPress `wp-content/plugins/` directory.
2.  Activate the plugin titled "LLM News Studio Pro" in your WordPress dashboard.
3.  Navigate to the **LLM News Studio** menu item to configure the required API keys:
    * **Groq API Key:** Get your key from [Groq Console](https://console.groq.com/keys).
    * **Unsplash Access Key:** Get your Client ID key from [Unsplash Developers](https://unsplash.com/developers).

## ⚙️ How It Works

The plugin provides two main functions:

| Feature | Execution Trigger | Description |
| :--- | :--- | :--- |
| **Manual Generation** | `⚡ Run Generator Now` Button | Instantly creates **one** post as a draft for testing and review. |
| **Daily Automation** | WordPress Cron (Scheduled) | Runs daily based on the **Daily Post Count** setting, generating content automatically without human intervention. |

## 🐞 Troubleshooting & Support

If you encounter an issue, please first check the error message returned on the **LLM News Studio** dashboard after running the generator manually.

| Issue | Resolution |
| :--- | :--- |
| **No Posts Generated** | Ensure your Groq API key is valid and you have tested the "Run Now" button. Errors are usually reported on the page. |
| **No Images Attached** | Verify the Unsplash Access Key. This system is robust, but connectivity issues (cURL/SSL) on the server side may interfere. Check the detailed "Image Status" message. |
| **Save Button Generates Post** | **Fixed in v5.1.** This was a Cron scheduling bug. The system now prevents the Save button from triggering instant execution. |

## 📄 License

This project is licensed under the GPL-2.0. See the [LICENSE.md](LICENSE.md) file for details.

---
*Developed by Gemini*

## 3. GitHub Upload Instructions

Follow these commands in your terminal (ensure Git is installed) from inside the root `llm-news-studio-pro/` folder:

1.  **Initialize the Repository:**
    ```bash
    git init
    ```
2.  **Add all files (excluding ignored files):**
    ```bash
    git add .
    ```
3.  **Commit the initial professional structure:**
    ```bash
    git commit -m "feat: Initial commit of LLM News Studio Pro v5.1 (OOP structure, Groq/Unsplash integration)"
    ```
4.  **Create your remote repository on GitHub** (e.g., name it `llm-news-studio-pro`).
5.  **Connect the local repository to the remote one** (replace the URL with your actual GitHub repository URL):
    ```bash
    git remote add origin [YOUR_GITHUB_REPOSITORY_URL]
    ```
6.  **Push the code:**
    ```bash
    git push -u origin master
    ```
