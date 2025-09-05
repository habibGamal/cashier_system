import React from "react";
import { Col, Empty, Row } from "antd";
import { OrderCard } from "@/Components/Orders/OrderCard";
import { CreateOrderButton } from "@/Components/Orders/CreateOrderButton";
import type { Order } from "@/types";

interface TakeawayTabProps {
    orders: Order[];
}

export const TakeawayTab: React.FC<TakeawayTabProps> = ({ orders }) => {
    // Sort orders
    const sortedOrders = [...orders].sort((a, b) => {
        if (a.status === b.status) {
            return 0;
        }
        return a.status > b.status ? -1 : 1;
    });

    return (
        <div>
            <Row gutter={[24, 16]}>
                <Col span={6}>
                    <CreateOrderButton orderType="takeaway" />
                </Col>
                {sortedOrders.map((order) => (
                    <Col span={6} key={order.id}>
                        <OrderCard
                            order={order}
                            href={`/orders/manage/${order.id}`}
                            orderType="takeaway"
                        />
                    </Col>
                ))}
            </Row>
            {sortedOrders.length === 0 && (
                <Empty
                    className="mx-auto"
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                    description="لا يوجد طلبات"
                />
            )}
        </div>
    );
};
