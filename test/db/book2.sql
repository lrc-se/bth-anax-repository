CREATE TABLE IF NOT EXISTS book (
    bookId INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    title VARCHAR NOT NULL,
    author VARCHAR NOT NULL,
    published DATETIME,
    deleted DATETIME
);