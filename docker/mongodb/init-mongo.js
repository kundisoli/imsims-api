// MongoDB initialization script
db = db.getSiblingDB('inventory_logs');

// Create collections for inventory system
db.createCollection('inventory_activities');
db.createCollection('product_metadata');
db.createCollection('audit_logs');
db.createCollection('performance_metrics');

// Create indexes for better performance
db.inventory_activities.createIndex({ "product_id": 1, "timestamp": -1 });
db.product_metadata.createIndex({ "product_id": 1 });
db.audit_logs.createIndex({ "user_id": 1, "timestamp": -1 });
db.performance_metrics.createIndex({ "endpoint": 1, "timestamp": -1 });

// Create user for the application
db.createUser({
    user: "inventory_app",
    pwd: "app_password",
    roles: [
        {
            role: "readWrite",
            db: "inventory_logs"
        }
    ]
});

print("MongoDB initialization completed");