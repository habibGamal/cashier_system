import { useState } from "react";
import { App, message } from "antd";
import { Order, OrderItemData, User } from "@/types";
import { IOrderService, IModalActions, OrderType, OrderStatus } from "@/types/OrderManagement";
import { OrderServiceFactory } from "@/services/OrderServices";
import getDirectSaleOrder from "@/helpers/getDirectSaleOrder";
import { get } from "http";

// Single Responsibility Principle - Each handler has one specific responsibility
export class OrderActionHandlers {
    private orderService: IOrderService;
    private modal: any;
    private orderStatus?: OrderStatus;

    constructor(orderType: OrderType, modal: any, orderStatus?: OrderStatus) {
        this.orderService = OrderServiceFactory.createService(orderType);
        this.modal = modal;
        this.orderStatus = orderStatus;
    }

    private isActionAllowed(action: string): boolean {
        if (!this.orderStatus) return true;

        // For completed orders: only cancel, print, and delete are allowed
        if (this.orderStatus === "completed") {
            return ["cancel", "print", "delete", "clear"].includes(action);
        }

        // For cancelled orders: only print and delete are allowed
        if (this.orderStatus === "cancelled") {
            return ["print", "delete", "clear"].includes(action);
        }

        // For other statuses, all actions are allowed
        return true;
    }

    async handleSave(
        orderId: number | null,
        orderItems: OrderItemData[],
        callback?: (page: any) => void,
        finish?: () => void
    ): Promise<void> {
        if (!this.isActionAllowed("save")) {
            message.error("لا يمكن حفظ هذا الطلب في الحالة الحالية");
            finish?.();
            return;
        }

        try {
            if (orderItems.length === 0 && !orderId) {
                message.error("يجب إضافة عناصر إلى الطلب قبل الحفظ");
                finish?.();
                return;
            }

            const result = await this.orderService.save(orderId, orderItems);
            callback?.(result);
        } catch (error) {
            console.error("Save error:", error);
        } finally {
            finish?.();
        }
    }

    async handlePrint(
        orderId: number | null,
        order: Order | null,
        orderItems: OrderItemData[],
        finish?: () => void
    ): Promise<void> {
        if (!this.isActionAllowed("print")) {
            message.error("لا يمكن طباعة هذا الطلب في الحالة الحالية");
            finish?.();
            return;
        }
        console.log("Print action invoked" ,orderId, order); // Debug log
        try {
            if (orderId && order) {
                // For existing orders
                if (this.orderStatus === "completed" || this.orderStatus === "cancelled") {
                    // Just print without saving for completed/cancelled orders
                    await this.orderService.print(orderId);
                } else {
                    // Save first then print for other statuses
                    await this.orderService.save(orderId, orderItems);
                    await this.orderService.print(orderId);
                }
            } else {
                // For new orders, save first then print
                const result = await this.orderService.save(orderId, orderItems);
                const createdOrder = result.props.order || getDirectSaleOrder(result.props.orders);
                await this.orderService.print(createdOrder.id);
            }
        } catch (error) {
            console.error("Print error:", error);
        } finally {
            finish?.();
        }
    }

    async handlePayment(
        orderId: number | null,
        orderItems: OrderItemData[],
        modalActions: IModalActions,
        finish?: () => void
    ): Promise<void> {
        if (!this.isActionAllowed("payment")) {
            message.error("لا يمكن إجراء الدفع لهذا الطلب في الحالة الحالية");
            finish?.();
            return;
        }

        try {
            await this.orderService.save(orderId, orderItems);
            modalActions.openPaymentModal();
        } catch (error) {
            console.error("Payment error:", error);
        } finally {
            finish?.();
        }
    }

    handleClearOrder(
        orderId: number | null,
        dispatch: any,
        user: User,
        setCurrentOrderId?: (id: number | null) => void
    ): void {
        if (!this.isActionAllowed("clear")) {
            message.error("لا يمكن مسح هذا الطلب في الحالة الحالية");
            return;
        }

        this.modal.confirm({
            title: "هل أنت متأكد من مسح الطلب؟",
            content: "سيتم فقدان جميع العناصر المضافة",
            okText: "نعم، امسح",
            cancelText: "إلغاء",
            onOk: async () => {
                try {
                    if (orderId) {
                        // For direct sale orders, use destroy method if available
                        if (this.orderService.destroy) {
                            await this.orderService.destroy(orderId);
                            window.location.reload();
                        } else {
                            // Fallback to cancel for other order types
                            await this.orderService.cancel(orderId);
                            window.location.reload();
                        }
                    } else {
                        dispatch({ type: "init", orderItems: [], user });
                        setCurrentOrderId?.(null);
                        message.success("تم مسح الطلب");
                    }
                } catch (error) {
                    console.error("Clear order error:", error);
                }
            },
        });
    }

    async handleDiscount(
        orderId: number | null,
        orderItems: OrderItemData[],
        modalActions: IModalActions,
        finish?: () => void
    ): Promise<void> {
        if (!this.isActionAllowed("discount")) {
            message.error("لا يمكن تطبيق خصم على هذا الطلب في الحالة الحالية");
            finish?.();
            return;
        }

        try {
            await this.orderService.save(orderId, orderItems);
            modalActions.openOrderDiscountModal();
        } catch (error) {
            console.error("Discount error:", error);
        } finally {
            finish?.();
        }
    }
}

// Hook to provide order action handlers
export const useOrderActions = (orderType: OrderType, orderStatus?: OrderStatus) => {
    const { modal } = App.useApp();
    const [handlers] = useState(() => new OrderActionHandlers(orderType, modal, orderStatus));

    return handlers;
};
