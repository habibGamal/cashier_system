import React from "react";
import { Descriptions } from "antd";
import { IOrderCalculations } from "@/types/OrderManagement";
import { formatCurrency } from "@/utils/orderCalculations";

interface OrderSummaryProps {
    calculations: IOrderCalculations;
    title?: string;
}

// Single Responsibility Principle - This component only displays order calculations
export const OrderSummary: React.FC<OrderSummaryProps> = ({
    calculations,
    title = "الحساب"
}) => {
    const paymentItems = [
        {
            key: "1",
            label: "المجموع",
            children: formatCurrency(calculations.subTotal),
        },
        {
            key: "2",
            label: "الضريبة",
            children: formatCurrency(calculations.tax),
        },
        {
            key: "3",
            label: "الخدمة",
            children: formatCurrency(calculations.service),
        },
        {
            key: "4",
            label: "الخصم",
            children: formatCurrency(Number(calculations.discount)),
        },
        {
            key: "5",
            label: "الاجمالي",
            children: formatCurrency(calculations.total),
        },
    ];

    return (
        <Descriptions
            bordered
            title={title}
            column={1}
            items={paymentItems}
        />
    );
};
