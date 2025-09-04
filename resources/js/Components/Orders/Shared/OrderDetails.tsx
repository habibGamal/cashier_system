import React from "react";
import { Empty, Typography, Divider } from "antd";
import { OrderItemData, User } from "@/types";
import OrderItem from "@/Components/Orders/OrderItem";
import { OrderSummary } from "./OrderSummary";
import { IOrderCalculations } from "@/types/OrderManagement";

interface OrderDetailsProps {
    orderItems: OrderItemData[];
    dispatch: any;
    disabled: boolean;
    user: User;
    calculations: IOrderCalculations;
    forWeb?: boolean;
}

// Single Responsibility Principle - This component only displays order details
export const OrderDetails: React.FC<OrderDetailsProps> = ({
    orderItems,
    dispatch,
    disabled,
    user,
    calculations,
    forWeb = false,
}) => {
    return (
        <div className="isolate mt-4">
            <Typography.Title className="mt-0" level={5}>
                تفاصيل الطلب
            </Typography.Title>
            {orderItems.length === 0 && (
                <Empty
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                    description="لم يتم إضافة أي عناصر بعد"
                />
            )}
            {orderItems.map((orderItem) => (
                <OrderItem
                    key={orderItem.product_id}
                    orderItem={orderItem}
                    dispatch={dispatch}
                    disabled={disabled}
                    user={user}
                    forWeb={forWeb}
                />
            ))}
            <Divider />
            <OrderSummary calculations={calculations} />
        </div>
    );
};
