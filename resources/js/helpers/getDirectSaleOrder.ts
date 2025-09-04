import type { Order } from "@/types";

export default function getDirectSaleOrder(orders: Order[]): Order | undefined {
    if (!orders || orders.length === 0) return undefined;
    return orders.find(
        (order) => order.type === "direct_sale" && order.status === "processing"
    );
}
