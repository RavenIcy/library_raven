# LIBRARY MANAGEMENT 
### This is a RESTful API for a library management system built with PHP, Slim Framework, and MySQL. It supports user registration, login, and features for managing authors, books, and their relationships.
## FEATURES
- **User Registration and Authentication**:
  - Register new users and authenticate them using JWT.
  - Secure API endpoints with middleware for token verification and revocation.
- **Author Management**:
  - Add and list authors.
- **Book Management**:
  - Add and list books.
- **Relationships**:
  - Manage relationships between books and authors.

## System Architecture

- **PHP**: Backend server scripting.
- **Slim Framework**: Lightweight framework for building RESTful APIs.
- **JWT (Firebase)**: Token-based authentication.
- **MySQL**: Relational database for persistent data storage.

---

## API Endpoints

### 1. **User Endpoints**

#### **Register a User**
- **Method**: `POST`
- **Endpoint**: `/user/register`
- **Request Body**:
  ```json
  {
    "username": "yourUsername",
    "password": "yourPassword"
  }
- **Request Body**:
  ```json
  {
  "status": "success",
  "data": null
  }
- **Request Body**:
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Error message here"
   }
  }

#### **Authenticate  a User**
- **Method**: `POST`
- **Endpoint**: `/user/authenticate`
- **Request Body**:
  ```json
  {
  "username": "yourUsername",
  "password": "yourPassword"
  }
- **Request Body**:
  ```json
  {
  "status": "success",
  "token": "your_jwt_token",
  "data": null
  }
- **Request Body**:
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Authentication Failed!"
  }
  }
### 2. **Author Endpoints**

#### **Add a new Author**
- **Method**: `POST`
- **Endpoint**: `/authors/add`
- **Request Body**:
  ```json
  {
  "name": "Author Name"
  }
- **Request Body**:
  ```json
  {
  "status": "success",
  "data": null
  }
#### **Get List of Authors**
- **Method**: `GET`
- **Endpoint**: `/authors`
- **Request Body**:
  ```json
  {
  "status": "success",
  "data": [
    {
      "authorid": 1,
      "name": "Author Name"
    },
    {
      "authorid": 2,
      "name": "Another Author"
    }
  ]
   }

### 3. **Book Endpointss**

#### **Add a new Book**
- **Method**: `POST`
- **Endpoint**: `/books/add`
- **Request Body**:
  ```json
  {
  "title": "Book Title",
  "authorid": 1
  }
- **Request Body**:
  ```json
  {
  "status": "success",
  "data": null
  }
#### **Get List of Books**
- **Method**: `GET`
- **Endpoint**: `/books`
- **Request Body**:
  ```json
  {
  "status": "success",
  "data": [
    {
      "bookid": 1,
      "title": "Book Title",
      "authorid": 1
    },
    {
      "bookid": 2,
      "title": "Another Book",
      "authorid": 2
    }
  ]
  }

### 4. **Book-Author Relationship Endpoints**

#### **Add a Relationship Between a Book and an Author**
- **Method**: `POST`
- **Endpoint**: `/books_authors/add`
- **Request Body**:
  ```json
  {
  "title": "Book Title",
  "authorid": 1
  }
- **Request Body**:
  ```json
  {
  "status": "success",
  "data": null
  }
 
