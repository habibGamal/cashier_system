import React from "react";
import { Link, router } from "@inertiajs/react";
import { Button, Col, Empty, Row, Typography, Badge } from "antd";
import { PhoneOutlined, PlusOutlined } from "@ant-design/icons";
import { orderStatus } from "@/helpers/orderState";
import useModal from "@/hooks/useModal";
import ChooseTableForm from "@/Components/Orders/ChooseTableForm";
import type { Order } from "@/types";

interface DineInTabProps {
    orders: Order[];
}

export const DineInTab: React.FC<DineInTabProps> = ({ orders }) => {
    const tableModal = useModal();

    // Sort orders
    const sortedOrders = [...orders].sort((a, b) => {
        if (a.status === b.status) {
            return 0;
        }
        return a.status > b.status ? -1 : 1;
    });

    const addTable = () => {
        tableModal.showModal();
    };

    const makeNewOrder = (values: any) => {
        const tableNumber = `${values.tableType} - ${values.tableNumber}`;
        router.post(route("orders.store"), {
            type: "dine_in",
            table_number: tableNumber,
        });
        tableModal.closeModal();
    };

    return (
        <div>
            <ChooseTableForm tableModal={tableModal} onFinish={makeNewOrder} />

            <Row gutter={[24, 16]}>
                <Col span={6}>
                    <Button
                        onClick={addTable}
                        className="h-32 w-full"
                        icon={<PlusOutlined style={{ fontSize: "48px" }} />}
                        type="primary"
                        size="large"
                    >
                        <div className="mt-2">إضافة طاولة جديدة</div>
                    </Button>
                </Col>

                {sortedOrders.map((order) => (
                    <Col span={6} key={order.id}>
                        <Link href={`/orders/manage/${order.id}`}>
                            <Badge.Ribbon {...orderStatus(order.status)}>
                                <div className="isolate grid place-items-center rounded-sm p-4 border h-32">
                                    <Typography.Title level={4}>
                                        طاولة{" "}
                                        {order.dine_table_number || "غير محدد"}
                                    </Typography.Title>
                                    <Typography.Title level={5}>
                                        # طلب رقم {order.order_number}
                                    </Typography.Title>
                                </div>
                            </Badge.Ribbon>
                        </Link>
                    </Col>
                ))}
            </Row>
            {sortedOrders.length === 0 && (
                <div className="mx-auto">
                    <Empty
                        className="mt-8"
                        image={Empty.PRESENTED_IMAGE_SIMPLE}
                        description="لا يوجد طلبات"
                    />
                </div>
            )}
        </div>
    );
};
