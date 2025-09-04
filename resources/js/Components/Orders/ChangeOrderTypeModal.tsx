import React from 'react';
import { Modal, Form, Radio, message } from 'antd';
import { router } from '@inertiajs/react';
import { Order } from '@/types';
import { getOrderTypeLabel } from '@/utils/orderCalculations';

interface ChangeOrderTypeModalProps {
    open: boolean;
    onCancel: () => void;
    order: Order;
}

export default function ChangeOrderTypeModal({ open, onCancel, order }: ChangeOrderTypeModalProps) {
    const [form] = Form.useForm();

    const orderTypes = [
        { value: 'takeaway', label: 'تيك أواي' },
        { value: 'delivery', label: 'دليفري' },
        { value: 'web_delivery', label: 'ويب دليفري' },
        { value: 'web_takeaway', label: 'ويب تيك أواي' },
        { value: 'direct_sale', label: 'بيع مباشر' },
    ];

    const onFinish = (values: any) => {
        router.post(`/orders/update-type/${order.id}`, values, {
            onSuccess: () => {
                message.success('تم تغيير نوع الطلب بنجاح');
                onCancel();
            },
            onError: () => {
                message.error('حدث خطأ أثناء تغيير نوع الطلب');
            },
        });
    };

    const handleCancel = () => {
        form.resetFields();
        onCancel();
    };

    return (
        <Modal
            title="تغيير نوع الطلب"
            open={open}
            onCancel={handleCancel}
            onOk={() => form.submit()}
            okText="تغيير"
            cancelText="إلغاء"
            destroyOnClose
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={onFinish}
                initialValues={{
                    type: order.type,
                }}
            >
                <Form.Item
                    name="type"
                    label="نوع الطلب الجديد"
                    rules={[{ required: true, message: 'نوع الطلب مطلوب' }]}
                >
                    <Radio.Group>
                        {orderTypes.map((type) => (
                            <Radio key={type.value} value={type.value}>
                                {type.label}
                            </Radio>
                        ))}
                    </Radio.Group>
                </Form.Item>
            </Form>
        </Modal>
    );
}
