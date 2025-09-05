import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Button,
    Card,
    Col,
    Descriptions,
    Row,
    Table,
    Typography,
    Tag,
    Space,
    message,
    Popconfirm,
    Badge,
    Breadcrumb
} from 'antd';
import {
    RightOutlined,
    PrinterOutlined,
    DeleteOutlined,
    ExclamationCircleOutlined,
    UndoOutlined
} from '@ant-design/icons';
import CashierLayout from '@/Layouts/CashierLayout';
import type { ReturnOrder } from '@/types';

const { Title, Text } = Typography;

interface ShowReturnOrderProps {
    returnOrder: ReturnOrder;
}

export default function ShowReturnOrder({ returnOrder }: ShowReturnOrderProps) {
    const handlePrint = () => {
        // TODO: Implement print functionality for return orders
        message.info('وظيفة الطباعة قيد التطوير');
    };

    const handleCancel = () => {
        router.delete(route('return-orders.destroy', returnOrder.id), {
            onSuccess: () => {
                message.success('تم إلغاء طلب الإرجاع بنجاح');
            },
            onError: () => {
                message.error('حدث خطأ في إلغاء طلب الإرجاع');
            }
        });
    };

    const getStatusConfig = (status: string) => {
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

    const statusConfig = getStatusConfig(returnOrder.status);

    const breadcrumbItems = [
        {
            title: 'طلبات الإرجاع',
        },
        {
            title: `طلب الإرجاع #${returnOrder.return_number}`,
        },
    ];

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
            title: 'الكمية المُرجعة',
            dataIndex: 'quantity',
            key: 'quantity',
            align: 'center' as const,
            render: (quantity: number) => (
                <Typography.Text>{quantity}</Typography.Text>
            ),
        },
        {
            title: 'السعر الأصلي',
            dataIndex: 'original_price',
            key: 'original_price',
            align: 'center' as const,
            render: (price: number) => (
                <Typography.Text>{Number(price).toFixed(2)} ج.م</Typography.Text>
            ),
        },
        {
            title: 'سعر الإرجاع',
            dataIndex: 'return_price',
            key: 'return_price',
            align: 'center' as const,
            render: (price: number) => (
                <Typography.Text style={{ color: '#52c41a' }}>
                    {Number(price).toFixed(2)} ج.م
                </Typography.Text>
            ),
        },
        {
            title: 'الإجمالي',
            dataIndex: 'total',
            key: 'total',
            align: 'center' as const,
            render: (total: number) => (
                <Typography.Text strong style={{ color: '#52c41a' }}>
                    {Number(total).toFixed(2)} ج.م
                </Typography.Text>
            ),
        },
        {
            title: 'السبب',
            dataIndex: 'reason',
            key: 'reason',
            render: (reason: string) => {
                const reasonText = {
                    'damaged': 'تالف',
                    'wrong_item': 'منتج خاطئ',
                    'customer_change_mind': 'تغيير رأي العميل',
                    'quality_issue': 'مشكلة في الجودة',
                    'other': 'أخرى'
                };
                const text = reasonText[reason as keyof typeof reasonText] || reason;
                return (
                    <Tag color="blue" style={{ borderRadius: '4px' }}>
                        {text}
                    </Tag>
                );
            },
        },
    ];

    return (
        <CashierLayout title={`طلب الإرجاع #${returnOrder.return_number}`}>
            <Head title={`طلب الإرجاع #${returnOrder.return_number}`} />

            <div className="p-4">
                {/* Header with Badge Ribbon - Following app patterns */}
                <Badge.Ribbon
                    color={statusConfig.color}
                    text={statusConfig.text}
                >
                    <div className="isolate flex gap-4 items-center mb-6">
                        <Link href={route('orders.index', { _hash: 'return_orders' })}>
                            <Button
                                size="large"
                                type="primary"
                                icon={<RightOutlined />}
                            />
                        </Link>
                        <Breadcrumb
                            className="text-2xl"
                            separator=">"
                            items={breadcrumbItems}
                        />
                    </div>
                </Badge.Ribbon>

                {/* Action Buttons */}
                <div className="mb-6">
                    <Space>
                        <Button
                            icon={<PrinterOutlined />}
                            onClick={handlePrint}
                            size="large"
                        >
                            طباعة
                        </Button>
                        {returnOrder.status === 'pending' && (
                            <Popconfirm
                                title="إلغاء طلب الإرجاع"
                                description="هل أنت متأكد من إلغاء طلب الإرجاع؟ هذا الإجراء لا يمكن التراجع عنه."
                                icon={<ExclamationCircleOutlined style={{ color: 'red' }} />}
                                onConfirm={handleCancel}
                                okText="نعم، إلغاء"
                                cancelText="تراجع"
                                okButtonProps={{ danger: true }}
                            >
                                <Button
                                    danger
                                    icon={<DeleteOutlined />}
                                    size="large"
                                >
                                    إلغاء طلب الإرجاع
                                </Button>
                            </Popconfirm>
                        )}
                    </Space>
                </div>

                <Row gutter={[24, 24]}>
                    {/* Return Order Details - Using consistent card structure */}
                    <Col span={12}>
                        <Card
                            title="تفاصيل طلب الإرجاع"
                            className="h-full"
                        >
                            <Descriptions column={1} size="small" bordered>
                                <Descriptions.Item label="رقم طلب الإرجاع">
                                    <Typography.Text strong>
                                        #{returnOrder.return_number}
                                    </Typography.Text>
                                </Descriptions.Item>
                                <Descriptions.Item label="الطلب الأصلي">
                                    <Link
                                        href={route('orders.manage', returnOrder.order.id)}
                                        className="text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        #{returnOrder.order.order_number}
                                    </Link>
                                </Descriptions.Item>
                                <Descriptions.Item label="تاريخ الإرجاع">
                                    {new Date(returnOrder.created_at).toLocaleString('ar-EG')}
                                </Descriptions.Item>
                                <Descriptions.Item label="المبلغ المسترد">
                                    <Typography.Text
                                        strong
                                        style={{ fontSize: '16px', color: '#52c41a' }}
                                    >
                                        {Number(returnOrder.refund_amount).toFixed(2)} ج.م
                                    </Typography.Text>
                                </Descriptions.Item>
                                <Descriptions.Item label="الحالة">
                                    <Tag color={statusConfig.color}>
                                        {statusConfig.text}
                                    </Tag>
                                </Descriptions.Item>
                                {returnOrder.reason && (
                                    <Descriptions.Item label="سبب الإرجاع">
                                        {returnOrder.reason}
                                    </Descriptions.Item>
                                )}
                                {returnOrder.notes && (
                                    <Descriptions.Item label="ملاحظات">
                                        {returnOrder.notes}
                                    </Descriptions.Item>
                                )}
                            </Descriptions>
                        </Card>
                    </Col>

                    {/* Customer & Employee Details - Improved layout */}
                    <Col span={12}>
                        <Card
                            title="تفاصيل العميل والموظف"
                            className="h-full"
                        >
                            <Descriptions column={1} size="small" bordered>
                                {returnOrder.customer ? (
                                    <>
                                        <Descriptions.Item label="اسم العميل">
                                            <Typography.Text strong>
                                                {returnOrder.customer.name}
                                            </Typography.Text>
                                        </Descriptions.Item>
                                        <Descriptions.Item label="رقم الهاتف">
                                            {returnOrder.customer.phone}
                                        </Descriptions.Item>
                                        {returnOrder.customer.address && (
                                            <Descriptions.Item label="العنوان">
                                                {returnOrder.customer.address}
                                            </Descriptions.Item>
                                        )}
                                    </>
                                ) : (
                                    <Descriptions.Item label="العميل">
                                        <Typography.Text type="secondary">
                                            غير محدد
                                        </Typography.Text>
                                    </Descriptions.Item>
                                )}
                                <Descriptions.Item label="الموظف">
                                    <Typography.Text strong>
                                        {returnOrder.user.name || returnOrder.user.email}
                                    </Typography.Text>
                                </Descriptions.Item>
                            </Descriptions>
                        </Card>
                    </Col>
                </Row>

                {/* Return Items Table - Improved design */}
                <Card
                    title="عناصر الإرجاع"
                    className="mt-6"
                >
                    <Table
                        dataSource={returnOrder.items}
                        columns={columns}
                        rowKey="id"
                        pagination={false}
                        scroll={{ x: 800 }}
                        size="small"
                        className="mb-4"
                        summary={(pageData) => {
                            let totalQuantity = 0;
                            let totalAmount = 0;

                            pageData.forEach(({ quantity, total }) => {
                                totalQuantity += quantity;
                                totalAmount += total;
                            });

                            return (
                                <Table.Summary.Row style={{ backgroundColor: '#f5f5f5' }}>
                                    <Table.Summary.Cell index={0}>
                                        <Typography.Text strong>الإجمالي</Typography.Text>
                                    </Table.Summary.Cell>
                                    <Table.Summary.Cell index={1}>
                                        <Typography.Text strong>{totalQuantity}</Typography.Text>
                                    </Table.Summary.Cell>
                                    <Table.Summary.Cell index={2}>-</Table.Summary.Cell>
                                    <Table.Summary.Cell index={3}>-</Table.Summary.Cell>
                                    <Table.Summary.Cell index={4}>
                                        <Typography.Text
                                            strong
                                            style={{ color: '#52c41a', fontSize: '16px' }}
                                        >
                                            {Number(totalAmount).toFixed(2)} ج.م
                                        </Typography.Text>
                                    </Table.Summary.Cell>
                                    <Table.Summary.Cell index={5}>-</Table.Summary.Cell>
                                </Table.Summary.Row>
                            );
                        }}
                    />
                </Card>
            </div>
        </CashierLayout>
    );
}
