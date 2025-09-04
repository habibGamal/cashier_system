import { Order, OrderItemData, User } from "@/types";

// Interface Segregation Principle - Split large interfaces into smaller, specific ones
export interface IOrderService {
    save(orderId: number | null, items: OrderItemData[]): Promise<any>;
    print(orderId: number): Promise<void>;
    cancel(orderId: number): Promise<void>;
    complete(orderId: number): Promise<void>;
    destroy?(orderId: number): Promise<void>;  // Optional, only for orders that support deletion
}

export interface IPaymentService {
    processPayment(order: Order, items: OrderItemData[]): Promise<void>;
}

export interface IOrderActions {
    onSave?: (finish: () => void) => void;
    onPayment?: (finish: () => void) => void;
    onPrint?: (finish: () => void) => void;
    onCancel?: () => void;
    onDiscount?: (finish: () => void) => void;
}

export interface IModalState {
    isCustomerModalOpen: boolean;
    isDriverModalOpen?: boolean;
    isOrderNotesModalOpen: boolean;
    isOrderDiscountModalOpen: boolean;
    isPaymentModalOpen: boolean;
    isChangeOrderTypeModalOpen?: boolean;
}

export interface IModalActions {
    openCustomerModal: () => void;
    closeCustomerModal: () => void;
    openDriverModal?: () => void;
    closeDriverModal?: () => void;
    openOrderNotesModal: () => void;
    closeOrderNotesModal: () => void;
    openOrderDiscountModal: () => void;
    closeOrderDiscountModal: () => void;
    openPaymentModal: () => void;
    closePaymentModal: () => void;
    openChangeOrderTypeModal?: () => void;
    closeChangeOrderTypeModal?: () => void;
}

export interface IOrderCalculations {
    subTotal: number;
    tax: number;
    service: number;
    discount: number;
    total: number;
}

export interface IOrderPermissions {
    canEdit: boolean;
    canSave: boolean;
    canPrint: boolean;
    canCancel: boolean;
    canComplete: boolean;
    canDiscount: boolean;
    canDelete: boolean;
}

export interface IOrderContext {
    order: Order | null;
    orderItems: OrderItemData[];
    user: User;
    permissions: IOrderPermissions;
    calculations: IOrderCalculations;
}

export type OrderType = 'direct_sale' | 'takeaway' | 'delivery' | 'web_delivery' | 'web_takeaway';
export type OrderStatus = 'pending' | 'processing' | 'completed' | 'cancelled' | 'out_for_delivery';

export interface IOrderStrategy {
    type: OrderType;
    save(orderId: number | null, items: OrderItemData[]): Promise<any>;
    complete(order: Order, items: OrderItemData[]): Promise<void>;
    getPermissions(order: Order): IOrderPermissions;
    getAvailableActions(order: Order): string[];
}
