import React, { useState } from 'react';
import { router, Link, usePage } from '@inertiajs/react';
import {
    Button,
    Input,
    message,
    Row,
    Col,
    Typography,
    Modal,
    Table,
    Form,
    InputNumber,
    Select,
    Empty,
    Card,
    Space,
    Badge,
    Descriptions
} from 'antd';
import {
    SearchOutlined,
    UndoOutlined,
    ExclamationCircleOutlined,
    EyeOutlined,
    PlusOutlined
} from '@ant-design/icons';
import type { ReturnOrder, Order } from '@/types';
import axios from 'axios';
import { formatCurrency } from '@/utils/currency';

const { confirm } = Modal;

interface ReturnOrdersTabProps {
    returnOrders?: ReturnOrder[];
}

export const ReturnOrdersTab: React.FC<ReturnOrdersTabProps> = ({ returnOrders = [] }) => {
    const [orderSearchValue, setOrderSearchValue] = useState('');
    const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
    const [returnModalVisible, setReturnModalVisible] = useState(false);
    const [loadingOrderSearch, setLoadingOrderSearch] = useState(false);
    const [form] = Form.useForm();

    // Helper function for return order status
    const getReturnOrderStatusConfig = (status: string) => {
        switch (status) {
            case 'pending':
                return { color: 'orange', text: 'في الانتظار' };
            case 'completed':
                return { color: 'green', text: 'مكتمل' };
            case 'cancelled':
                return { color: 'red', text: 'ملغي' };
            default:
                return { color: 'default', text: status };
        }
    };
    // Search for order by ID
    const searchOrder = async () => {
        if (!orderSearchValue.trim()) {
            message.error('يرجى إدخال رقم الطلب');
            return;
        }

        setLoadingOrderSearch(true);
        try {
            // Use axios for API call that returns JSON
            const orderResponse = await axios.get<Order>(route('return-orders.details', { orderId: orderSearchValue }));
            if (orderResponse.data) {
                setSelectedOrder(orderResponse.data);
                setTimeout(() => {
                    setReturnModalVisible(true);
                }, 0);
                form.resetFields();
            } else {
                message.error('لم يتم العثور على الطلب');
            }
        } catch (error: any) {
            if (error.response?.status === 404) {
                message.error('لم يتم العثور على الطلب');
            } else if (error.response?.data?.message) {
                message.error(error.response.data.message);
            } else {
                message.error('حدث خطأ في البحث عن الطلب');
            }
            console.error(error);
        } finally {
            setLoadingOrderSearch(false);
        }
    };

    // Handle return order submission
    const handleReturnOrder = async (values: any) => {
        if (!selectedOrder) return;

        // Validate that at least one item is selected
        const returnItems = values.return_items?.filter((item: any) => item && item.quantity > 0) || [];

        if (returnItems.length === 0) {
            message.error('يرجى اختيار عنصر واحد على الأقل للإرجاع');
            return;
        }
        console.log(returnItems,values);
        confirm({
            title: 'تأكيد إرجاع الطلب',
            icon: <ExclamationCircleOutlined />,
            content: `هل أنت متأكد من إرجاع العناصر المحددة من الطلب رقم ${selectedOrder.order_number}؟`,
            okText: 'تأكيد',
            cancelText: 'إلغاء',
            onOk() {
                // Use router for Inertia request that returns redirect/page
                router.post(route('return-orders.store'), {
                    order_id: selectedOrder.id,
                    items: returnItems,
                    reason: values.reason,
                    notes: values.notes
                }, {
                    onSuccess: () => {
                        message.success('تم إنشاء طلب الإرجاع بنجاح');
                        setReturnModalVisible(false);
                        setSelectedOrder(null);
                        setOrderSearchValue('');
                        form.resetFields();
                    },
                    onError: (errors) => {
                        if (errors.message) {
                            message.error(errors.message);
                        } else {
                            message.error('حدث خطأ في إنشاء طلب الإرجاع');
                        }
                    }
                });
            }
        });
    };

    // Columns for order items table in modal - Improved styling
    const columns = [
        {
            title: 'المنتج',
            dataIndex: ['product', 'name'],
            key: 'product_name',
            render: (name: string) => (
                <Typography.Text strong>{name}</Typography.Text>
            ),
        },
        {
            title: 'الكمية الأصلية',
            dataIndex: 'quantity',
            key: 'original_quantity',
            align: 'center' as const,
            render: (quantity: number) => (
                <Typography.Text>{quantity}</Typography.Text>
            ),
        },
        {
            title: 'متاح للإرجاع',
            dataIndex: 'available_for_return',
            key: 'available_for_return',
            align: 'center' as const,
            render: (available: number) => (
                <Typography.Text style={{ color: '#52c41a' }}>
                    {available || 0}
                </Typography.Text>
            ),
        },
        {
            title: 'السعر',
            dataIndex: 'price',
            key: 'price',
            align: 'center' as const,
            render: (price: number) => (
                <Typography.Text>{formatCurrency(price)}</Typography.Text>
            ),
        },
        {
            title: 'كمية الإرجاع',
            key: 'return_quantity',
            align: 'center' as const,
            render: (_: any, record: any, index: number) => (
                <Form.Item
                    name={['return_items', index, 'quantity']}
                    style={{ margin: 0 }}
                    rules={[
                        {
                            type: 'number',
                            min: 0,
                            max: record.available_for_return || record.quantity,
                            message: `الحد الأقصى ${record.available_for_return || record.quantity}`,
                        },
                    ]}
                >
                    <InputNumber
                        min={0}
                        max={record.available_for_return || record.quantity}
                        style={{ width: '100%' }}
                        placeholder="0"
                        size="small"
                    />
                </Form.Item>
            ),
        },
        {
            title: 'سبب الإرجاع',
            key: 'item_reason',
            render: (_: any, record: any, index: number) => (
                <Form.Item
                    name={['return_items', index, 'reason']}
                    style={{ margin: 0 }}
                >
                    <Select
                        placeholder="اختر السبب"
                        allowClear
                        size="small"
                        style={{ minWidth: 120 }}
                        options={[
                            { value: 'damaged', label: 'تالف' },
                            { value: 'wrong_item', label: 'منتج خاطئ' },
                            { value: 'customer_change_mind', label: 'تغيير رأي العميل' },
                            { value: 'quality_issue', label: 'مشكلة في الجودة' },
                            { value: 'other', label: 'أخرى' },
                        ]}
                    />
                </Form.Item>
            ),
        },
    ];

    // Hidden form fields for order item data
    const generateHiddenFields = () => {
        if (!selectedOrder?.items) return null;

        return selectedOrder.items.map((item, index) => (
            <div key={item.id}>
                <Form.Item name={['return_items', index, 'order_item_id']} hidden initialValue={item.id}>
                    <Input value={item.id} />
                </Form.Item>
                <Form.Item name={['return_items', index, 'product_id']} hidden initialValue={item.product_id}>
                    <Input value={item.product_id} />
                </Form.Item>
                <Form.Item name={['return_items', index, 'return_price']} hidden initialValue={item.price}>
                    <Input value={item.price} />
                </Form.Item>
            </div>
        ));
    };

    return (
        <div className="p-4">
            {/* Create Return Order Section - Improved design */}
            <Card
                title="إنشاء طلب إرجاع جديد"
                className="mb-6"
            >
                <Row gutter={[16, 16]} align="middle">
                    <Col span={16}>
                        <Input
                            placeholder="أدخل رقم الطلب (Order ID)"
                            value={orderSearchValue}
                            onChange={(e) => setOrderSearchValue(e.target.value)}
                            onPressEnter={searchOrder}
                            size="large"
                            prefix={<SearchOutlined />}
                        />
                    </Col>
                    <Col span={8}>
                        <Button
                            type="primary"
                            onClick={searchOrder}
                            loading={loadingOrderSearch}
                            size="large"
                            block
                            icon={<SearchOutlined />}
                        >
                            البحث عن الطلب
                        </Button>
                    </Col>
                </Row>
            </Card>

            {/* Return Orders List - Following app card patterns */}
            <Card
                title="طلبات الإرجاع"
                className="mb-4"
            >
                {returnOrders.length === 0 ? (
                    <Empty
                        image={Empty.PRESENTED_IMAGE_SIMPLE}
                        description="لا توجد طلبات إرجاع"
                        className="my-8"
                    />
                ) : (
                    <Row gutter={[24, 16]}>
                        {returnOrders.map((returnOrder) => {
                            const statusConfig = getReturnOrderStatusConfig(returnOrder.status);
                            return (
                                <Col span={8} key={returnOrder.id}>
                                    <Badge.Ribbon
                                        color={statusConfig.color}
                                        text={statusConfig.text}
                                    >
                                        <Card
                                            className="hover:shadow-md transition-shadow cursor-pointer"
                                            size="small"
                                            actions={[
                                                <Link
                                                    key="view"
                                                    href={route('return-orders.show', returnOrder.id)}
                                                >
                                                    <Button
                                                        type="link"
                                                        icon={<EyeOutlined />}
                                                        className="text-blue-600"
                                                    >
                                                        عرض التفاصيل
                                                    </Button>
                                                </Link>
                                            ]}
                                        >
                                            <Descriptions column={1} size="small">
                                                <Descriptions.Item label="رقم الإرجاع">
                                                    <Typography.Text strong>
                                                        #{returnOrder.return_number}
                                                    </Typography.Text>
                                                </Descriptions.Item>
                                                <Descriptions.Item label="الطلب الأصلي">
                                                    <Typography.Text>
                                                        #{returnOrder.order.order_number}
                                                    </Typography.Text>
                                                </Descriptions.Item>
                                                <Descriptions.Item label="المبلغ المسترد">
                                                    <Typography.Text
                                                        strong
                                                        style={{ color: '#52c41a' }}
                                                    >
                                                        {formatCurrency(returnOrder.refund_amount)}
                                                    </Typography.Text>
                                                </Descriptions.Item>
                                                <Descriptions.Item label="التاريخ">
                                                    <Typography.Text type="secondary">
                                                        {new Date(returnOrder.created_at).toLocaleDateString('ar-EG')}
                                                    </Typography.Text>
                                                </Descriptions.Item>
                                                {returnOrder.customer && (
                                                    <Descriptions.Item label="العميل">
                                                        {returnOrder.customer.name}
                                                    </Descriptions.Item>
                                                )}
                                            </Descriptions>
                                        </Card>
                                    </Badge.Ribbon>
                                </Col>
                            );
                        })}
                    </Row>
                )}
            </Card>

            {/* Return Order Modal - Improved design */}
            <Modal
                title={
                    <Space>
                        <UndoOutlined style={{ color: '#1890ff' }} />
                        <span>إرجاع طلب #{selectedOrder?.order_number || ''}</span>
                    </Space>
                }
                open={returnModalVisible}
                onCancel={() => {
                    setReturnModalVisible(false);
                    setSelectedOrder(null);
                    form.resetFields();
                }}
                width={1200}
                footer={null}
                className="return-order-modal"
            >
                {selectedOrder && (
                    <Form
                        form={form}
                        onFinish={handleReturnOrder}
                        layout="vertical"
                        initialValues={{
                            return_items: selectedOrder.items?.map(() => ({
                                quantity: 0,
                                reason: '',
                            })),
                        }}
                    >
                        {/* Order Info Card */}
                        <Card
                            title="معلومات الطلب الأصلي"
                            className="mb-4"
                        >
                            <Descriptions column={4} size="small" bordered>
                                <Descriptions.Item label="رقم الطلب">
                                    <Typography.Text strong>
                                        {selectedOrder.order_number}
                                    </Typography.Text>
                                </Descriptions.Item>
                                <Descriptions.Item label="العميل">
                                    <Typography.Text>
                                        {selectedOrder.customer?.name || 'غير محدد'}
                                    </Typography.Text>
                                </Descriptions.Item>
                                <Descriptions.Item label="التاريخ">
                                    <Typography.Text>
                                        {new Date(selectedOrder.created_at).toLocaleDateString('ar-EG')}
                                    </Typography.Text>
                                </Descriptions.Item>
                                <Descriptions.Item label="الإجمالي">
                                    <Typography.Text strong style={{ color: '#52c41a' }}>
                                        {formatCurrency(selectedOrder.total)}
                                    </Typography.Text>
                                </Descriptions.Item>
                            </Descriptions>
                        </Card>

                        {/* Order Items Table */}
                        <Card
                            title="عناصر الطلب"
                            className="mb-4"
                        >
                            <Table
                                dataSource={selectedOrder.items}
                                columns={columns}
                                pagination={false}
                                rowKey="id"
                                size="small"
                                scroll={{ x: 800 }}
                            />
                        </Card>

                        {/* Hidden form fields */}
                        {generateHiddenFields()}

                        {/* General Return Info */}
                        <Card
                            title="معلومات الإرجاع"
                            className="mb-4"
                            size="small"
                        >
                            <Row gutter={[16, 16]}>
                                <Col span={12}>
                                    <Form.Item
                                        label="سبب الإرجاع العام"
                                        name="reason"
                                    >
                                        <Select
                                            placeholder="اختر السبب"
                                            size="large"
                                            options={[
                                                { value: 'damaged', label: 'تالف' },
                                                { value: 'wrong_order', label: 'طلب خاطئ' },
                                                { value: 'customer_change_mind', label: 'تغيير رأي العميل' },
                                                { value: 'quality_issue', label: 'مشكلة في الجودة' },
                                                { value: 'other', label: 'أخرى' },
                                            ]}
                                        />
                                    </Form.Item>
                                </Col>
                                <Col span={12}>
                                    <Form.Item
                                        label="ملاحظات"
                                        name="notes"
                                    >
                                        <Input.TextArea
                                            placeholder="ملاحظات إضافية (اختياري)"
                                            rows={3}
                                            size="large"
                                        />
                                    </Form.Item>
                                </Col>
                            </Row>
                        </Card>

                        {/* Form Actions */}
                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button
                                size="large"
                                onClick={() => {
                                    setReturnModalVisible(false);
                                    setSelectedOrder(null);
                                    form.resetFields();
                                }}
                            >
                                إلغاء
                            </Button>
                            <Button
                                type="primary"
                                htmlType="submit"
                                icon={<UndoOutlined />}
                                size="large"
                            >
                                إنشاء طلب الإرجاع
                            </Button>
                        </div>
                    </Form>
                )}
            </Modal>
        </div>
    );
};
