export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    role: 'admin' | 'cashier' | 'waiter' | 'kitchen';
}

export type OrderStatus = 'processing' | 'completed' | 'cancelled';
export type OrderType = 'dine_in' | 'takeaway' | 'delivery' | 'companies' | 'talabat';
export type PaymentMethod = 'cash' | 'card' | 'talabat_card';
export type PaymentStatus = 'pending' | 'partial_paid' | 'full_paid';

export interface Category {
    id: number;
    name: string;
    products: Product[];
}

export interface Product {
    id: number;
    category_id: number;
    name: string;
    price: number;
    cost: number;
    type: string;
    unit: string;
    printer_id?: number;
    legacy: boolean;
}

export interface Customer {
    id: number;
    name: string;
    phone: string;
    address?: string;
    delivery_cost?: number;
}

export interface Driver {
    id: number;
    name: string;
    phone: string;
}

export interface Table {
    id: number;
    name: string;
    capacity: number;
    is_active: boolean;
}

export interface OrderItem {
    id: number;
    order_id: number;
    product_id: number;
    quantity: number;
    price: number;
    notes?: string;
    product: Product;
}

export interface Payment {
    id: number;
    order_id: number;
    method: string;
    amount: number;
    user_id: number;
}

export interface Order {
    id: number;
    table_id?: number;
    customer_name?: string;
    phone?: string;
    status: OrderStatus;
    type: OrderType;
    total: number;
    subtotal: number;
    tax: number;
    discount: number;
    notes?: string;
    created_at: string;
    updated_at: string;
    items?: OrderItem[];
    table?: Table;
    payments?: Payment[];
}

export interface OrderItemData {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    notes?: string;
    initial_quantity?: number;
}

export type OrderItemAction =
    | { type: 'add'; orderItem: OrderItemData; user: User }
    | { type: 'remove'; id: number; user: User }
    | { type: 'increment'; id: number; user: User }
    | { type: 'decrement'; id: number; user: User }
    | { type: 'changeQuantity'; id: number; quantity: number; user: User }
    | { type: 'changeNotes'; id: number; notes: string; user: User }
    | { type: 'delete'; id: number; user: User }
    | { type: 'init'; orderItems: OrderItemData[]; user: User };

export interface ManageOrderProps {
    order: Order;
    categories: Category[];
    receiptFooter?: string;
}

export interface PageProps {
    auth: {
        user: User;
    };
    receiptFooter: string;
}

export interface OrdersPageProps extends PageProps {
    orders?: Order[];
    categories?: Category[];
    products?: Product[];
    tables?: Table[];
}

export interface Shift {
    id: number;
    user_id: number;
    start_at: string;
    end_at?: string;
    start_cash: number;
    end_cash?: number;
    losses_amount?: number;
    real_cash?: number;
    has_deficit: boolean;
    closed: boolean;
    is_active: boolean;
    duration: string;
    total_sales: number;
    total_cash: number;
    deficit: number;
    user?: User;
    orders?: Order[];
}

export interface OrderFilters {
    status?: OrderStatus;
    type?: OrderType;
    table_id?: number;
    date_from?: string;
    date_to?: string;
}

export interface ExpenseType {
    id: number;
    name: string;
}

export interface Expense {
    id: number;
    shift_id: number;
    expence_type_id: number;
    amount: number;
    notes?: string;
    created_at: string;
    updated_at: string;
    expenceType?: ExpenseType;
}
