import React, { useEffect } from 'react';
import { Button, Form, InputNumber, Modal, Radio, Spin } from 'antd';
import useModal from '@/hooks/useModal';
import useTableTypes from '@/hooks/useTableTypes';

interface ChooseTableFormProps {
    tableModal: ReturnType<typeof useModal>;
    onFinish: (values: any) => void;
}

export default function ChooseTableForm({ onFinish, tableModal }: ChooseTableFormProps) {
    const [form] = Form.useForm();
    const { tableTypes, loading, error } = useTableTypes();

    // Set the first table type as default when table types are loaded
    useEffect(() => {
        if (tableTypes.length > 0 && tableModal.open) {
            form.setFieldsValue({
                tableType: tableTypes[0].name,
            });
        }
    }, [tableTypes, tableModal.open, form]);

    const options = tableTypes.map(tableType => ({
        label: tableType.name,
        value: tableType.name,
    }));

    return (
        <Modal
            title="الطاولة"
            open={tableModal.open}
            onCancel={tableModal.onCancel}
            footer={null}
            destroyOnClose
        >
            {loading ? (
                <div className="flex justify-center items-center py-8">
                    <Spin size="large" />
                </div>
            ) : (
                <Form onFinish={onFinish} form={form} name="tableNumber" layout="vertical">
                    <Form.Item
                        label="نوع الطاولة"
                        name="tableType"
                        rules={[{ required: true, message: 'يرجى اختيار نوع الطاولة' }]}
                    >
                        <Radio.Group
                            options={options}
                            optionType="button"
                            buttonStyle="solid"
                        />
                    </Form.Item>
                    <Form.Item
                        label="رقم الطاولة"
                        name="tableNumber"
                        rules={[{ required: true, message: 'يرجى اختيار رقم الطاولة' }]}
                    >
                        <InputNumber min={1} className="w-full" />
                    </Form.Item>
                    <Form.Item>
                        <Button type="primary" htmlType="submit">
                            إضافة
                        </Button>
                    </Form.Item>
                    {error && (
                        <div className="text-red-500 text-sm mt-2">
                            {error}
                        </div>
                    )}
                </Form>
            )}
        </Modal>
    );
}
