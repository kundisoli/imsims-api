export interface Product {
    id: number;
    name: string;
    description?: string;
    sku: string;
    barcode?: string;
    category_id?: number;
    supplier_id?: number;
    cost_price: number;
    selling_price: number;
    min_stock_level: number;
    max_stock_level: number;
    reorder_point: number;
    unit_of_measure: string;
    weight?: number;
    dimensions?: any;
    is_active: boolean;
    is_trackable: boolean;
    created_at: string;
    updated_at: string;
    category?: Category;
    supplier?: Supplier;
    current_stock?: number;
    profit_margin?: number;
    metadata?: ProductMetadata;
}

export interface Category {
    id: number;
    name: string;
    description?: string;
    parent_id?: number;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
    parent?: Category;
    children?: Category[];
    products_count?: number;
    full_path?: string;
}

export interface Supplier {
    id: number;
    name: string;
    company_name?: string;
    email?: string;
    phone?: string;
    address?: string;
    city?: string;
    state?: string;
    postal_code?: string;
    country?: string;
    tax_number?: string;
    payment_terms?: string;
    credit_limit?: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    full_address?: string;
    total_products?: number;
}

export interface Warehouse {
    id: number;
    name: string;
    code: string;
    description?: string;
    address?: string;
    city?: string;
    state?: string;
    postal_code?: string;
    country?: string;
    manager_id?: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    manager?: any;
    locations?: Location[];
    full_address?: string;
    total_inventory_value?: number;
    total_products?: number;
}

export interface Location {
    id: number;
    warehouse_id: number;
    name: string;
    code: string;
    type?: string;
    aisle?: string;
    rack?: string;
    shelf?: string;
    bin?: string;
    barcode?: string;
    is_active: boolean;
    capacity?: number;
    temperature_controlled: boolean;
    hazardous_materials: boolean;
    created_at: string;
    updated_at: string;
    warehouse?: Warehouse;
    full_path?: string;
    utilization_percentage?: number;
    available_capacity?: number;
}

export interface InventoryRecord {
    id: number;
    product_id: number;
    warehouse_id: number;
    location_id?: number;
    quantity: number;
    reserved_quantity: number;
    available_quantity: number;
    last_counted_at?: string;
    last_movement_at?: string;
    created_at: string;
    updated_at: string;
    product?: Product;
    warehouse?: Warehouse;
    location?: Location;
}

export interface StockMovement {
    id: number;
    product_id: number;
    warehouse_id: number;
    location_id?: number;
    user_id: number;
    type: 'in' | 'out' | 'transfer' | 'adjustment';
    reason: 'purchase' | 'sale' | 'return' | 'damage' | 'theft' | 'expired' | 'transfer' | 'adjustment' | 'initial';
    quantity: number;
    reference_type?: string;
    reference_id?: number;
    notes?: string;
    unit_cost?: number;
    total_cost?: number;
    created_at: string;
    updated_at: string;
    product?: Product;
    warehouse?: Warehouse;
    location?: Location;
    user?: any;
}

export interface ProductMetadata {
    product_id: number;
    images?: any[];
    tags?: string[];
    attributes?: any;
    specifications?: any;
    variants?: any[];
    seo_data?: any;
    custom_fields?: any;
    supplier_info?: any;
    certifications?: any[];
    warnings?: any[];
    storage_requirements?: any;
}

export interface InventoryOverview {
    total_products: number;
    total_inventory_value: number;
    low_stock_products: number;
    out_of_stock_products: number;
    recent_movements: StockMovement[];
    top_products_by_value: any[];
    warehouse_summary: any[];
}

export interface StockAdjustment {
    product_id: number;
    warehouse_id: number;
    location_id?: number;
    quantity_change: number;
    reason: string;
    notes?: string;
    unit_cost?: number;
}

export interface StockTransfer {
    product_id: number;
    from_warehouse_id: number;
    from_location_id?: number;
    to_warehouse_id: number;
    to_location_id?: number;
    quantity: number;
    notes?: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: {
        first: string;
        last: string;
        prev?: string;
        next?: string;
    };
}