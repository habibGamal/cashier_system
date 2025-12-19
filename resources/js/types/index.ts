export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
  role: string;
}

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
  legacy: boolean;
  barcode?: string;
}

export interface Customer {
  id: number;
  name: string;
  phone: string;
  address?: string;
  delivery_cost?: number;
  hasWhatsapp?: boolean;
  region?: string;
  deliveryCost?: number;
}

export interface Driver {
  id: number;
  name: string;
  phone: string;
}

export interface Region {
  id: number;
  name: string;
  deliveryCost: number;
}

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  quantity: number;
  price: number;
  notes?: string;
  product: Product;
  available_for_return?: number;
  already_returned?: number;
  item_discount?: number;
  item_discount_type?: string;
  item_discount_percent?: number;
}

export interface Payment {
  id: number;
  order_id: number;
  method: 'cash' | 'card' | 'talabat_card';
  amount: number;
  user_id: number;
}

export interface ReturnItem {
  id: number;
  return_order_id: number;
  order_item_id: number;
  product_id: number;
  quantity: number;
  original_price: number;
  original_cost: number;
  return_price: number;
  total: number;
  reason?: string;
  product: Product;
  order_item: OrderItem;
}

export interface ReturnOrder {
  id: number;
  order_id: number;
  customer_id?: number;
  user_id: number;
  shift_id: number;
  return_number: number;
  status: string;
  refund_amount: number;
  reason?: string;
  notes?: string;
  created_at: string;
  updated_at: string;
  order: Order;
  customer?: Customer;
  user: User;
  items: ReturnItem[];
}

export interface ReturnItemData {
  order_item_id: number;
  product_id: number;
  quantity: number;
  return_price: number;
  reason?: string;
}

export interface Order {
  id: number;
  customer_id?: number;
  service_rate?: number;
  driver_id?: number;
  user_id: number;
  shift_id: number;
  type: 'dine_in' | 'takeaway' | 'delivery' | 'companies' | 'talabat' | 'web_delivery' | 'web_takeaway' | 'direct_sale';
  status: 'pending' | 'processing' | 'ready' | 'out_for_delivery' | 'completed' | 'cancelled';
  sub_total: number;
  tax: number;
  service: number;
  discount: number;
  temp_discount_percent: number;
  total: number;
  profit: number;
  payment_status: 'pending' | 'partial_paid' | 'full_paid';
  order_notes?: string;
  order_number: number;
  created_at: string;
  updated_at: string;
  customer?: Customer;
  driver?: Driver;
  items: OrderItem[];
  payments: Payment[];
  user?: User;
}

export interface OrderItemData {
  product_id: number;
  name: string;
  price: number;
  quantity: number;
  notes?: string;
  initial_quantity?: number;
  item_discount?: number;
  item_discount_type?: string;      // 'percent' | 'value'
  item_discount_percent?: number;
  product?: Product;
}

export type OrderItemAction =
  | { type: 'add'; orderItem: OrderItemData; user: User }
  | { type: 'remove'; id: number; user: User }
  | { type: 'increment'; id: number; user: User }
  | { type: 'decrement'; id: number; user: User }
  | { type: 'changeQuantity'; id: number; quantity: number; user: User }
  | { type: 'changeNotes'; id: number; notes: string; user: User }
  | { type: 'changeItemDiscount'; id: number; discount: number; discountType: string; discountPercent?: number; user: User }
  | { type: 'delete'; id: number; user: User }
  | { type: 'addByBarcode'; barcode: string; products: Product[]; scalePrefix?: string; user: User }
  | { type: 'init'; orderItems: OrderItemData[]; user: User };

export type PageProps<T = {}> = T & {
  auth: {
    user: User;
  };
  flash?: {
    success?: string;
    error?: string;
  };
  receiptFooter?: string;
  scaleBarcodePrefix?: string;
};

export interface ManageOrderProps extends PageProps {
  order: Order;
  categories: Category[];
  drivers: Driver[];
  regions: Region[];
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
  expence_type?: ExpenseType;
}
