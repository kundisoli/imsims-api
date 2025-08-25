import axios from 'axios';
import type { 
    Product, 
    Category, 
    Warehouse, 
    InventoryOverview, 
    StockAdjustment, 
    StockTransfer,
    PaginatedResponse 
} from '@/types/inventory';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Products API
export const productsApi = {
    getAll: (params?: any) => api.get<PaginatedResponse<Product>>('/products', { params }),
    getById: (id: number) => api.get<Product>(`/products/${id}`),
    create: (data: Partial<Product>) => api.post<{ message: string; product: Product }>('/products', data),
    update: (id: number, data: Partial<Product>) => api.put<{ message: string; product: Product }>(`/products/${id}`, data),
    delete: (id: number) => api.delete<{ message: string }>(`/products/${id}`),
    getLowStock: () => api.get<Product[]>('/products/reports/low-stock'),
    getStockSummary: (id: number) => api.get(`/products/${id}/stock-summary`),
};

// Categories API
export const categoriesApi = {
    getAll: (params?: any) => api.get<PaginatedResponse<Category>>('/categories', { params }),
    getById: (id: number) => api.get<Category>(`/categories/${id}`),
    create: (data: Partial<Category>) => api.post<{ message: string; category: Category }>('/categories', data),
    update: (id: number, data: Partial<Category>) => api.put<{ message: string; category: Category }>(`/categories/${id}`, data),
    delete: (id: number) => api.delete<{ message: string }>(`/categories/${id}`),
    getTree: () => api.get<Category[]>('/categories/tree/all'),
    getProducts: (id: number, params?: any) => api.get<PaginatedResponse<Product>>(`/categories/${id}/products`, { params }),
};

// Warehouses API
export const warehousesApi = {
    getAll: (params?: any) => api.get<PaginatedResponse<Warehouse>>('/warehouses', { params }),
    getById: (id: number) => api.get<Warehouse>(`/warehouses/${id}`),
    create: (data: Partial<Warehouse>) => api.post<{ message: string; warehouse: Warehouse }>('/warehouses', data),
    update: (id: number, data: Partial<Warehouse>) => api.put<{ message: string; warehouse: Warehouse }>(`/warehouses/${id}`, data),
    delete: (id: number) => api.delete<{ message: string }>(`/warehouses/${id}`),
    getLocations: (id: number, params?: any) => api.get(`/warehouses/${id}/locations`, { params }),
    getInventorySummary: (id: number) => api.get(`/warehouses/${id}/inventory-summary`),
    getPerformanceMetrics: (id: number, params?: any) => api.get(`/warehouses/${id}/performance-metrics`, { params }),
};

// Inventory API
export const inventoryApi = {
    getOverview: () => api.get<InventoryOverview>('/inventory/overview'),
    getByWarehouse: (warehouseId: number, params?: any) => api.get(`/inventory/warehouse/${warehouseId}`, { params }),
    adjustStock: (adjustments: StockAdjustment[]) => api.post('/inventory/adjust-stock', { adjustments }),
    transferStock: (transfer: StockTransfer) => api.post('/inventory/transfer-stock', transfer),
    getMovementHistory: (params?: any) => api.get('/inventory/movement-history', { params }),
};

// Request interceptor to add CSRF token
api.interceptors.request.use((config) => {
    const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

// Response interceptor for error handling
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Redirect to login or handle unauthorized
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;