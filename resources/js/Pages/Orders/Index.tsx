import React, { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { Button, Descriptions, Form, InputNumber, Modal, Popconfirm, Tabs } from 'antd';
import CashierLayout from '@/Layouts/CashierLayout';
import { DineInTab } from '@/Components/Orders/Index/DineInTab';
import { DeliveryTab } from '@/Components/Orders/Index/DeliveryTab';
import { TakeawayTab } from '@/Components/Orders/Index/TakeawayTab';
import { TalabatTab } from '@/Components/Orders/Index/TalabatTab';
import { CompaniesTab } from '@/Components/Orders/Index/CompaniesTab';
import { DisplayTab } from '@/Components/Orders/Index/DisplayTab';
import { ReceiveOrdersPaymentsTab } from '@/Components/Orders/Index/ReceiveOrdersPaymentsTab';
import { ShiftExpensesTab } from '@/Components/Orders/Index/ShiftExpensesTab';
import WebDeliveryTab from '@/Components/Orders/Index/WebDeliveryTab';
import WebTakeawayTab from '@/Components/Orders/Index/WebTakeawayTab';
import type { Order, User } from '@/types';

interface IndexProps {
    orders: Order[];
    previousPartialPaidOrders: Order[];
    auth: {
        user: User;
    };
}

const OrdersIndex: React.FC<IndexProps> = ({
    orders,
    auth
}) => {
    const { user } = auth;
    const dineInOrders = orders.filter(
        (order) => order.type === 'dine_in' && order.status === 'processing'
    );
    const takeawayOrders = orders.filter(
        (order) => order.type === 'takeaway' && order.status === 'processing'
    );
    const deliveryOrders = orders.filter(
        (order) => order.type === 'delivery' && order.status === 'processing'
    );
    const talabatOrders = orders.filter(
        (order) => order.type === 'talabat' && order.status === 'processing'
    );
    const webDeliveryOrders = orders.filter(
        (order) => order.type === 'web_delivery' && ['pending', 'processing', 'out_for_delivery'].includes(order.status)
    );
    const webTakeawayOrders = orders.filter(
        (order) => order.type === 'web_takeaway' && ['pending', 'processing'].includes(order.status)
    );
    const cancelledOrders = orders.filter((order) => order.status === 'cancelled');
    const completedOrders = orders.filter((order) => order.status === 'completed');

    const [tab, setTab] = useState(window.location.hash.replace('#', '') || 'dine_in');
    const [endShiftModalVisible, setEndShiftModalVisible] = useState(false);
    const [form] = Form.useForm();

    const endShift = (values: any) => {
        router.post(route('shifts.end'), { ...values });
        setEndShiftModalVisible(false);
    };

    const endShiftWithZero = () => {
        router.post(route('shifts.end'), { real_end_cash: 0 });
    };

    const tabItems = [
        {
            label: 'الصالة',
            children: <DineInTab orders={dineInOrders} />,
            key: 'dine_in',
        },
        {
            label: 'الديلفري',
            children: <DeliveryTab orders={deliveryOrders} />,
            key: 'delivery',
        },
        {
            label: 'التيك اواي',
            children: <TakeawayTab orders={takeawayOrders} />,
            key: 'takeaway',
        },
        {
            label: 'طلبات',
            children: <TalabatTab orders={talabatOrders} />,
            key: 'talabat',
        },
        {
            label: 'ويب ديلفري',
            children: <WebDeliveryTab orders={webDeliveryOrders} />,
            key: 'web_delivery',
        },
        {
            label: 'ويب تيك اواي',
            children: <WebTakeawayTab orders={webTakeawayOrders} />,
            key: 'web_takeaway',
        },
        {
            label: 'مصاريف',
            children: <ShiftExpensesTab />,
            key: 'expenses',
        },
        {
            label: 'ملغي',
            children: <DisplayTab orders={cancelledOrders} />,
            key: 'cancelled',
        },
        {
            label: 'منتهي',
            children: <DisplayTab orders={completedOrders} />,
            key: 'completed',
        },
    ];

    return (
        <CashierLayout>
            <Head title="إدارة الطلبات" />

            <div className="p-4">
                <div className="flex justify-between">
                    <Descriptions data-testid="user-info">
                        <Descriptions.Item label="الموظف">{user?.email}</Descriptions.Item>
                    </Descriptions>

                    <Popconfirm
                        title="هل انت متأكد؟"
                        onConfirm={() => endShiftWithZero()}
                        okText="نعم"
                        cancelText="لا"
                    >
                        <Button>انهاء الشيفت</Button>
                    </Popconfirm>

                    <Modal
                        open={endShiftModalVisible}
                        onCancel={() => setEndShiftModalVisible(false)}
                        title="انهاء الشيفت"
                        footer={null}
                    >
                        <Form onFinish={endShift} layout="vertical" form={form}>
                            <Form.Item
                                name="real_end_cash"
                                label="النقدية في الدرج"
                                rules={[
                                    {
                                        required: true,
                                        message: 'النقدية في الدرج مطلوبة',
                                    },
                                ]}
                            >
                                <InputNumber min={0} className="w-full" />
                            </Form.Item>
                            <Form.Item>
                                <Button htmlType="submit" type="primary">
                                    تم
                                </Button>
                            </Form.Item>
                        </Form>
                    </Modal>
                </div>

                <Tabs
                    onChange={(key) => {
                        setTab(key);
                        window.location.hash = key;
                    }}
                    activeKey={tab}
                    type="card"
                    size="large"
                    items={tabItems}
                />
            </div>
        </CashierLayout>
    );
};

export default OrdersIndex;
