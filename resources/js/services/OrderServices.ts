import { router } from "@inertiajs/react";
import { message } from "antd";
import { Order, OrderItemData } from "@/types";
import {
    IOrderService,
    IPaymentService,
    OrderType,
} from "@/types/OrderManagement";
import axios from "axios";

// Single Responsibility Principle - Each service has one clear responsibility
export class OrderService implements IOrderService {
    async save(orderId: number | null, items: OrderItemData[]): Promise<any> {
        const itemsForApi = items.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.price,
            notes: item.notes || null,
        }));

        return new Promise((resolve, reject) => {
            if (orderId) {
                router.post(
                    `/orders/save-order/${orderId}`,
                    { items: itemsForApi },
                    {
                        onSuccess: (page) => {
                            message.success("تم حفظ الطلب بنجاح");
                            resolve(page);
                        },
                        onError: (errors) => {
                            message.error("فشل في حفظ الطلب");
                            reject(errors);
                        },
                    }
                );
            } else {
                router.post(
                    route("orders.store"),
                    {
                        type: "direct_sale",
                        items: itemsForApi,
                    },
                    {
                        onSuccess: (page) => {
                            message.success("تم إنشاء وحفظ الطلب بنجاح");
                            resolve(page);
                        },
                        onError: (errors) => {
                            message.error("فشل في حفظ الطلب");
                            reject(errors);
                        },
                    }
                );
            }
        });
    }

    async print(orderId: number): Promise<void> {
        axios.post(`/orders/print/${orderId}`);
        message.success("تم إرسال طلب الطباعة");
    }

    async cancel(orderId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(
                `/orders/cancel-order/${orderId}`,
                {},
                {
                    onSuccess: () => {
                        message.success("تم إلغاء الطلب");
                        resolve();
                    },
                    onError: () => {
                        message.error("فشل في إلغاء الطلب");
                        reject();
                    },
                }
            );
        });
    }

    async destroy(orderId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.delete(route("orders.destroy", orderId), {
                onSuccess: () => {
                    message.success("تم حذف الطلب بنجاح");
                    resolve();
                },
                onError: (errors: any) => {
                    const errorMessage = errors?.message || "فشل في حذف الطلب";
                    message.error(errorMessage);
                    reject(errors);
                },
            });
        });
    }

    async complete(orderId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(
                `/orders/complete-order/${orderId}`,
                {},
                {
                    onSuccess: () => {
                        message.success("تم إنهاء الطلب");
                        resolve();
                    },
                    onError: () => {
                        message.error("فشل في إنهاء الطلب");
                        reject();
                    },
                }
            );
        });
    }
}

export class WebOrderService implements IOrderService {
    async save(orderId: number | null, items: OrderItemData[]): Promise<any> {
        const itemsWithNotes = items.map((item) => ({
            product_id: item.product_id,
            notes: item.notes,
        }));

        return new Promise((resolve, reject) => {
            router.post(
                `/web-orders/save-order/${orderId}`,
                { items: itemsWithNotes },
                {
                    onSuccess: (page) => {
                        message.success("تم حفظ الطلب بنجاح");
                        resolve(page);
                    },
                    onError: () => {
                        message.error("فشل في حفظ الطلب");
                        reject();
                    },
                }
            );
        });
    }

    async print(orderId: number): Promise<void> {
        axios.post(`/orders/print/${orderId}`);
        message.success("تم إرسال طلب الطباعة");
    }

    async cancel(orderId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(
                `/web-orders/reject-order/${orderId}`,
                {},
                {
                    onSuccess: () => {
                        message.success("تم إلغاء الطلب");
                        resolve();
                    },
                    onError: () => {
                        message.error("فشل في إلغاء الطلب");
                        reject();
                    },
                }
            );
        });
    }

    async complete(orderId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(
                `/web-orders/accept-order/${orderId}`,
                {},
                {
                    onSuccess: () => {
                        message.success("تم قبول الطلب بنجاح");
                        resolve();
                    },
                    onError: () => {
                        message.error("فشل في قبول الطلب");
                        reject();
                    },
                }
            );
        });
    }

    // Web orders don't support destroy operation
    // destroy method is intentionally not implemented
}

export class PaymentService implements IPaymentService {
    async processPayment(order: Order, items: OrderItemData[]): Promise<void> {
        // This method will trigger the payment modal
        // The actual payment processing is handled by the PaymentModal component
        return Promise.resolve();
    }
}

// Factory Pattern - Open/Closed Principle
export class OrderServiceFactory {
    static createService(orderType: OrderType): IOrderService {
        switch (orderType) {
            case "web_delivery":
            case "web_takeaway":
                return new WebOrderService();
            case "direct_sale":
            case "takeaway":
            case "delivery":
            default:
                return new OrderService();
        }
    }
}
