import React from "react";
import { Link, router } from "@inertiajs/react";
import { Button, Col, Empty, Row, Typography, Badge } from "antd";
import { PhoneOutlined } from "@ant-design/icons";
import { orderStatus } from "@/helpers/orderState";
import type { Order } from "@/types";

interface DeliveryTabProps {
    orders: Order[];
}

export const DeliveryTab: React.FC<DeliveryTabProps> = ({ orders }) => {
    // Sort orders
    const sortedOrders = [...orders].sort((a, b) => {
        if (a.status === b.status) {
            return 0;
        }
        return a.status > b.status ? -1 : 1;
    });

    const createOrder = () => {
        router.post(route("orders.store"), { type: "delivery" });
    };

    return (
        <div>
            <Row gutter={[24, 16]}>
                <Col span={6}>
                    <Button
                        onClick={createOrder}
                        className="h-32 w-full"
                        type="primary"
                        size="large"
                    >
                        <div
                            className="mt-2"
                            style={{
                                fontSize: "18px",
                                textAlign: "center",
                                width: "100%",
                            }}
                        >
                            إنشاء طلب ديلفري
                        </div>
                    </Button>
                </Col>

                {sortedOrders.map((order) => (
                    <Col span={6} key={order.id}>
                        <Link href={`/orders/manage/${order.id}`}>
                            <Badge.Ribbon {...orderStatus(order.status)}>
                                <div className="isolate grid place-items-center gap-4 rounded-sm p-4 border">
                                    <Typography.Title level={4}>
                                        # طلب رقم {order.order_number}
                                    </Typography.Title>
                                    {order.customer?.name && (
                                        <Typography.Text>
                                            اسم العميل:{order.customer?.name}
                                        </Typography.Text>
                                    )}
                                    <Typography.Text>
                                        السائق:{" "}
                                        {order.driver?.name || "غير معروف"}
                                    </Typography.Text>
                                    <Typography.Title
                                        className="flex items-center gap-2"
                                        level={5}
                                    >
                                        <PhoneOutlined
                                            style={{ color: "#d7a600" }}
                                        />
                                        رقم العميل{" "}
                                        {order.customer?.phone || "غير معروف"}
                                    </Typography.Title>
                                </div>
                            </Badge.Ribbon>
                        </Link>
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
