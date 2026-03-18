/**
 * Database helper for CLI agent.
 * Uses mysql2/promise for async queries.
 */

const mysql = require('mysql2/promise');

let connection = null;

async function getDb(dbConfig) {
    if (!connection) {
        connection = await mysql.createConnection({
            host: dbConfig.host,
            user: dbConfig.user,
            password: dbConfig.password,
            database: dbConfig.database,
        });
    }
    return connection;
}

async function closeDb() {
    if (connection) {
        await connection.end();
        connection = null;
    }
}

module.exports = { getDb, closeDb };
