import React from 'react';
import { Modal, Form, InputNumber, Radio, message } from 'antd';
import { router } from '@inertiajs/react';
import { Order } from '@/types';

interface OrderDiscountModalProps {
    open: boolean;
    onCancel: () => void;
    order: Order;
}

export default function OrderDiscountModal({ open, onCancel, order }: OrderDiscountModalProps) {
    const [form] = Form.useForm();

    const onFinish = (values: any) => {
        router.post(`/orders/apply-discount/${order.id}`, values, {
            onSuccess: () => {
                message.success('تم تطبيق الخصم بنجاح');
                onCancel();
            },
            onError: () => {
                message.error('حدث خطأ أثناء تطبيق الخصم');
            },
        });
    };

    const handleCancel = () => {
        form.resetFields();
        onCancel();
    };

    return (
        <Modal
            title="تطبيق خصم"
            open={open}
            onCancel={handleCancel}
            onOk={() => form.submit()}
            okText="تطبيق"
            cancelText="إلغاء"
            destroyOnClose
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={onFinish}
                initialValues={{
                    discount: 0,
                    discount_type: 'value',
                }}
            >
                <Form.Item
                    name="discount_type"
                    label="نوع الخصم"
                >
                    <Radio.Group>
                        <Radio value="value">مبلغ ثابت</Radio>
                        <Radio value="percent">نسبة مئوية</Radio>
                    </Radio.Group>
                </Form.Item>

                <Form.Item
                    name="discount"
                    label="قيمة الخصم"
                    rules={[{ required: true, message: 'قيمة الخصم مطلوبة' }]}
                >
                    <InputNumber
                        min={0}
                        style={{ width: '100%' }}
                        placeholder="ادخل قيمة الخصم"
                    />
                </Form.Item>
            </Form>
        </Modal>
    );
}
