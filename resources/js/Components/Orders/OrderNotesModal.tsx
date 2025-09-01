import React from 'react';
import { Modal, Form, Input, message } from 'antd';
import { router } from '@inertiajs/react';
import { Order } from '@/types';

const { TextArea } = Input;

interface OrderNotesModalProps {
    open: boolean;
    onCancel: () => void;
    order: Order;
}

export default function OrderNotesModal({ open, onCancel, order }: OrderNotesModalProps) {
    const [form] = Form.useForm();

    const onFinish = (values: any) => {
        router.post(`/orders/update-notes/${order.id}`, values, {
            onSuccess: () => {
                message.success('تم حفظ ملاحظات الطلب بنجاح');
                onCancel();
            },
            onError: () => {
                message.error('حدث خطأ أثناء حفظ ملاحظات الطلب');
            },
        });
    };

    const handleCancel = () => {
        form.resetFields();
        onCancel();
    };

    return (
        <Modal
            title="ملاحظات الطلب"
            open={open}
            onCancel={handleCancel}
            onOk={() => form.submit()}
            okText="حفظ"
            cancelText="إلغاء"
            destroyOnClose
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={onFinish}
                initialValues={{
                    order_notes: order.order_notes || '',
                }}
            >
                <Form.Item
                    name="order_notes"
                    label="ملاحظات الطلب"
                >
                    <TextArea
                        placeholder="اكتب ملاحظات الطلب هنا..."
                        rows={4}
                    />
                </Form.Item>
            </Form>
        </Modal>
    );
}
