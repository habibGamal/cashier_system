import React from "react";
import { Link } from "@inertiajs/react";
import { Badge, Typography } from "antd";
import { PhoneOutlined } from "@ant-design/icons";
import { orderStatus } from "@/helpers/orderState";
import type { Order } from "@/types";

interface OrderCardProps {
    order: Order;
    href: string;
    orderType: "takeaway" | "delivery" | "web-delivery" | "web-takeaway" | "display";
    showDriverInfo?: boolean;
}

const orderTypeConfig = {
    takeaway: {
        color: "blue",
        hoverColor: "blue-300",
        bgColor: "blue-50",
        textColor: "blue-600",
        label: "تيك أواي"
    },
    delivery: {
        color: "green",
        hoverColor: "green-300",
        bgColor: "green-50",
        textColor: "green-600",
        label: "ديلفري"
    },
    "web-delivery": {
        color: "purple",
        hoverColor: "purple-300",
        bgColor: "purple-50",
        textColor: "purple-600",
        label: "ويب ديلفري"
    },
    "web-takeaway": {
        color: "indigo",
        hoverColor: "indigo-300",
        bgColor: "indigo-50",
        textColor: "indigo-600",
        label: "ويب تيك أواي"
    },
    display: {
        color: "gray",
        hoverColor: "gray-400",
        bgColor: "gray-50",
        textColor: "gray-600",
        label: "عرض الطلبات"
    }
};

export const OrderCard: React.FC<OrderCardProps> = ({
    order,
    href,
    orderType,
    showDriverInfo = false
}) => {
    const config = orderTypeConfig[orderType];

    // For web orders, use custom status logic
    const getWebOrderStatus = (status: string) => {
        const statusConfig = {
            pending: { color: 'orange', text: 'في الإنتظار' },
            processing: { color: 'blue', text: 'قيد التشغيل' },
            out_for_delivery: { color: 'purple', text: 'في طريق التوصيل' },
            completed: { color: 'green', text: 'مكتمل' },
            cancelled: { color: 'red', text: 'ملغي' },
        };
        return statusConfig[status as keyof typeof statusConfig] || { color: 'gray', text: status };
    };

    const isWebOrder = orderType.includes('web');
    const statusProps = isWebOrder ? getWebOrderStatus(order.status) : orderStatus(order.status);

    return (
        <Link href={href}>
            <Badge.Ribbon
                color={isWebOrder ? statusProps.color : statusProps.color}
                text={isWebOrder ? statusProps.text : statusProps.text}
            >
                <div className={`group relative bg-white border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-all duration-200 hover:border-${config.hoverColor} cursor-pointer`}>
                    <div className="text-center space-y-3">
                        <div className={`flex items-center justify-center w-12 h-12 bg-${config.bgColor} rounded-full mx-auto mb-3`}>
                            <Typography.Text className={`text-${config.textColor} font-bold text-lg`}>
                                #{order.order_number}
                            </Typography.Text>
                        </div>

                        <Typography.Title
                            level={4}
                            className={`!mb-2 !text-gray-800 group-hover:!text-${config.textColor} transition-colors`}
                        >
                            طلب رقم {order.order_number}
                        </Typography.Title>

                        {order.customer?.name && (
                            <div className="text-gray-700">
                                <Typography.Text className="text-sm font-medium">
                                    {order.customer.name}
                                </Typography.Text>
                            </div>
                        )}

                        <div className="flex items-center justify-center gap-2 text-gray-600">
                            <PhoneOutlined className="text-amber-500" />
                            <Typography.Text className="text-sm">
                                {order.customer?.phone || "غير معروف"}
                            </Typography.Text>
                        </div>

                        {showDriverInfo && (
                            <div className="text-gray-600">
                                <Typography.Text className="text-sm">
                                    السائق: {order.driver?.name || "غير معروف"}
                                </Typography.Text>
                            </div>
                        )}

                        <div className="pt-2 border-t border-gray-100">
                            <Typography.Text className="text-xs text-gray-500">
                                {config.label}
                            </Typography.Text>
                        </div>
                    </div>
                </div>
            </Badge.Ribbon>
        </Link>
    );
};
