import React from 'react';
import { Link } from '@inertiajs/react';
import { Col, Empty, Row, Typography, Badge } from 'antd';
import { PhoneOutlined } from '@ant-design/icons';
import { orderStatus } from '@/helpers/orderState';
import type { Order } from '@/types';

interface DisplayTabProps {
    orders: Order[];
}

export const DisplayTab: React.FC<DisplayTabProps> = ({ orders }) => {
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
                {sortedOrders.length === 0 && (
                    <Empty
                        className="!mx-auto"
                        image={Empty.PRESENTED_IMAGE_SIMPLE}
                        description="لا يوجد طلبات"
                    />
                )}

                {sortedOrders.map((order) => (
                    <Col span={6} key={order.id}>
                        <Link href={`/orders/manage/${order.id}`}>
                            <Badge.Ribbon {...orderStatus(order.status)}>
                                <div className="isolate grid place-items-center gap-4 rounded-sm p-4 border">
                                    <Typography.Title level={4}>
                                        # طلب رقم {order.order_number}
                                    </Typography.Title>
                                    <Typography.Title className="flex items-center gap-2" level={5}>
                                        <PhoneOutlined style={{ color: "#d7a600" }} />
                                        رقم العميل {order.customer?.phone || 'غير معروف'}
                                    </Typography.Title>
                                </div>
                            </Badge.Ribbon>
                        </Link>
                    </Col>
                ))}
            </Row>
        </div>
    );
};
