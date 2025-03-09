# KeensEcom
Keens Web Application

Overview
This is the official web application for Keens. This document provides information on how to set up, run, and maintain the application, including details about its structure and the technologies used.

Tech Stack
The application is built using the following technologies:
•	Frontend: HTML, CSS, JavaScript, Bootstrap
•	Backend: PHP
•	Database: MySQL
•	Version Control: Git

Project Structure
The project consists of the following main parts:
•	Frontend: Contains all HTML, CSS, and JavaScript files for UI/UX.
•	Backend: PHP scripts handling business logic and database interactions.
•	Database: MySQL database storing user data, transactions, and other relevant information.
•	Config Files: Includes environment settings and database configurations.

Setup Instructions

Prerequisites
Ensure you have the following installed:
•	PHP
•	MySQL
•	Apache Server (XAMPP, WAMP, or LAMP)
•	Git

Installation Steps

1.	Download the Repository:

2.	cd KeensEcom

3.	Move Files to Server Directory:
o	Copy the project folder to the server root directory (e.g., htdocs for XAMPP).

4.	Set Up the Database:
o	Open phpMyAdmin and create a new database.
o	Import the provided SQL file (database/ast.sql) to set up tables.
o	Update database connection details in components/connect.php.

5.	Run the Application:
o	Start Apache and MySQL from XAMPP/WAMP.
o	Open a web browser and visit http://localhost/ast.

Deployment
To deploy the application, follow these steps:
•	Upload project files to a web hosting server.
•	Set up the MySQL database on the hosting server and import the SQL file.
•	Update connect.php with the live database credentials.

Contribution Guidelines
•	Fork the repository and create a new branch for features/bug fixes.
•	Follow coding best practices and maintain clean documentation.
•	Submit a pull request for review.
