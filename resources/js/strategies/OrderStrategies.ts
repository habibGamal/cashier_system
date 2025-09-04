import { Order, OrderItemData, User } from "@/types";
import { IOrderStrategy, IOrderPermissions, OrderType, OrderStatus } from "@/types/OrderManagement";
import { OrderService, WebOrderService } from "@/services/OrderServices";

// Strategy Pattern + Factory Pattern - Open/Closed Principle
abstract class BaseOrderStrategy implements IOrderStrategy {
    abstract type: OrderType;

    abstract save(orderId: number | null, items: OrderItemData[]): Promise<any>;
    abstract complete(order: Order, items: OrderItemData[]): Promise<void>;

    getPermissions(order: Order): IOrderPermissions {
        const isProcessing = order.status === "processing";
        const isCompleted = order.status === "completed";
        const isCancelled = order.status === "cancelled";

        return {
            canEdit: isProcessing,
            canSave: isProcessing,
            canPrint: true,
            canCancel: isCompleted, // Only completed orders can be cancelled (not already cancelled ones)
            canComplete: isProcessing,
            canDiscount: isProcessing,
            canDelete: true,
        };
    }

    getAvailableActions(order: Order): string[] {
        const actions = ["print"];

        if (order.status === "processing") {
            actions.push("save", "complete", "discount", "notes");
        }

        if (order.status === "completed") {
            actions.push("cancel"); // Only cancel action for completed orders
        }

        // No additional actions for cancelled orders, only print

        return actions;
    }
}

class DirectSaleStrategy extends BaseOrderStrategy {
    type: OrderType = "direct_sale";
    private service = new OrderService();

    async save(orderId: number | null, items: OrderItemData[]): Promise<any> {
        return this.service.save(orderId, items);
    }

    async complete(order: Order, items: OrderItemData[]): Promise<void> {
        // For direct sales, completion is handled by payment modal
        return Promise.resolve();
    }

    getPermissions(order: Order): IOrderPermissions {
        const isProcessing = order.status === "processing";
        const isCompleted = order.status === "completed";
        const isCancelled = order.status === "cancelled";

        // Direct sale orders follow the same rules as other orders
        return {
            canEdit: isProcessing,
            canSave: isProcessing,
            canPrint: true,
            canCancel: isCompleted, // Only completed orders can be cancelled
            canComplete: isProcessing,
            canDiscount: isProcessing,
            canDelete: true,
        };
    }
}

class TakeawayStrategy extends BaseOrderStrategy {
    type: OrderType = "takeaway";
    private service = new OrderService();

    async save(orderId: number | null, items: OrderItemData[]): Promise<any> {
        return this.service.save(orderId, items);
    }

    async complete(order: Order, items: OrderItemData[]): Promise<void> {
        return this.service.complete(order.id);
    }

    getAvailableActions(order: Order): string[] {
        const actions = super.getAvailableActions(order);

        if (order.status === "processing") {
            actions.push("customer", "changeOrderType");
        }

        return actions;
    }
}

class DeliveryStrategy extends BaseOrderStrategy {
    type: OrderType = "delivery";
    private service = new OrderService();

    async save(orderId: number | null, items: OrderItemData[]): Promise<any> {
        return this.service.save(orderId, items);
    }

    async complete(order: Order, items: OrderItemData[]): Promise<void> {
        return this.service.complete(order.id);
    }

    getAvailableActions(order: Order): string[] {
        const actions = super.getAvailableActions(order);

        if (order.status === "processing") {
            actions.push("customer", "driver", "changeOrderType");
        }

        return actions;
    }
}

class WebDeliveryStrategy extends BaseOrderStrategy {
    type: OrderType = "web_delivery";
    private service = new WebOrderService();

    async save(orderId: number | null, items: OrderItemData[]): Promise<any> {
        return this.service.save(orderId, items);
    }

    async complete(order: Order, items: OrderItemData[]): Promise<void> {
        return this.service.complete(order.id);
    }

    getPermissions(order: Order): IOrderPermissions {
        const isProcessing = order.status === "processing";
        const isCompleted = order.status === "completed";
        const isCancelled = order.status === "cancelled";
        const canEdit = ["pending", "processing"].includes(order.status);

        return {
            canEdit: canEdit,
            canSave: canEdit,
            canPrint: true,
            canCancel: isCompleted, // Only completed orders can be cancelled
            canComplete: isProcessing,
            canDiscount: isProcessing,
            canDelete: true,
        };
    }

    getAvailableActions(order: Order): string[] {
        const actions = ["print"];

        if (order.status === "pending") {
            actions.push("accept", "cancel");
        }

        if (order.status === "processing") {
            actions.push("save", "complete", "discount", "notes", "driver", "cancel");
        }

        if (order.status === "out_for_delivery") {
            actions.push("complete", "driver", "save", "cancel");
        }

        if (order.status === "completed") {
            actions.push("cancel"); // Only cancel action for completed orders
        }

        // No additional actions for cancelled orders, only print

        return actions;
    }
}

class WebTakeawayStrategy extends BaseOrderStrategy {
    type: OrderType = "web_takeaway";
    private service = new WebOrderService();

    async save(orderId: number | null, items: OrderItemData[]): Promise<any> {
        return this.service.save(orderId, items);
    }

    async complete(order: Order, items: OrderItemData[]): Promise<void> {
        return this.service.complete(order.id);
    }

    getPermissions(order: Order): IOrderPermissions {
        const isProcessing = order.status === "processing";
        const isCompleted = order.status === "completed";
        const isCancelled = order.status === "cancelled";
        const canEdit = ["pending", "processing"].includes(order.status);

        return {
            canEdit: canEdit,
            canSave: canEdit,
            canPrint: true,
            canCancel: isCompleted, // Only completed orders can be cancelled
            canComplete: isProcessing,
            canDiscount: isProcessing,
            canDelete: true,
        };
    }

    getAvailableActions(order: Order): string[] {
        const actions = ["print"];

        if (order.status === "pending") {
            actions.push("accept", "cancel");
        }

        if (order.status === "processing") {
            actions.push("save", "complete", "discount", "notes", "cancel");
        }

        if (order.status === "completed") {
            actions.push("cancel"); // Only cancel action for completed orders
        }

        // No additional actions for cancelled orders, only print

        return actions;
    }
}

// Factory Pattern - Open/Closed Principle
export class OrderStrategyFactory {
    static createStrategy(orderType: OrderType): IOrderStrategy {
        switch (orderType) {
            case "direct_sale":
                return new DirectSaleStrategy();
            case "takeaway":
                return new TakeawayStrategy();
            case "delivery":
                return new DeliveryStrategy();
            case "web_delivery":
                return new WebDeliveryStrategy();
            case "web_takeaway":
                return new WebTakeawayStrategy();
            default:
                throw new Error(`Unknown order type: ${orderType}`);
        }
    }
}
