/**
 * Queue Poller
 *
 * Polls vtiger_ai_agent_queue for pending requests and builds actions.
 * Run as a background service or cron job.
 *
 * Usage:
 *   node poller.js              # Poll once
 *   node poller.js --daemon     # Continuous polling
 */

const path = require('path');
const { buildAction } = require('./builder');
const { getDb, closeDb } = require('./db');

const configPath = path.join(__dirname, 'config.js');
const config = require(configPath);

async function pollOnce() {
    const db = await getDb(config.db);

    try {
        // Get pending items (oldest first)
        const [rows] = await db.execute(
            "SELECT * FROM vtiger_ai_agent_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1"
        );

        if (rows.length === 0) {
            return false; // nothing to process
        }

        const item = rows[0];
        console.log(`\nProcessing queue item #${item.id}: ${item.requested_action}`);

        const params = item.params ? JSON.parse(item.params) : {};
        const description = params.description || `User requested capability: ${item.requested_action}`;

        // Close DB before build (builder manages its own connection)
        await closeDb();

        const result = await buildAction(item.requested_action, description, item.id);

        if (result.success) {
            console.log(`Queue item #${item.id} completed: ${result.actionName}`);
        } else {
            console.log(`Queue item #${item.id} failed: ${result.errors.join(', ')}`);
        }

        return true; // processed something

    } catch (error) {
        console.error('Poll error:', error.message);
        return false;
    }
}

async function daemon() {
    console.log(`Poller started (interval: ${config.pollInterval}s)`);
    console.log('Press Ctrl+C to stop.\n');

    while (true) {
        const processed = await pollOnce();

        if (!processed) {
            // Nothing in queue, wait before next poll
            await new Promise(resolve => setTimeout(resolve, config.pollInterval * 1000));
        } else {
            // Processed something, check immediately for more
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
    }
}

// Entry point
const isDaemon = process.argv.includes('--daemon');

if (isDaemon) {
    daemon().catch(err => {
        console.error('Daemon error:', err);
        process.exit(1);
    });
} else {
    pollOnce().then(processed => {
        if (!processed) console.log('No pending items in queue.');
        process.exit(0);
    }).catch(err => {
        console.error(err);
        process.exit(1);
    });
}
