import React from 'react';
import { Head, router } from '@inertiajs/react';
import { Button, Form, InputNumber } from 'antd';
import CashierLayout from '@/Layouts/CashierLayout';

const StartShift: React.FC = () => {
    const onFinish = (values: any) => {
        router.post(route('shifts.store'), values);
    };

    return (
        <CashierLayout>
            <Head title="بداية الوردية" />

            <div className="grid place-items-center w-full min-h-[50vh]">
                <Form
                    name="startShift"
                    className="isolate min-w-[500px]"
                    layout="vertical"
                    onFinish={onFinish}
                >
                    <Form.Item
                        label="النقود المتوفرة"
                        name="start_cash"
                        rules={[{ required: true, message: 'هذا الحقل مطلوب' }]}
                    >
                        <InputNumber min={0} className="w-full" placeholder="ادخل النقود المتوفرة" />
                    </Form.Item>

                    <Form.Item>
                        <Button type="primary" htmlType="submit" size="large" className="w-full">
                            بداية الوردية
                        </Button>
                    </Form.Item>
                </Form>
            </div>
        </CashierLayout>
    );
};

export default StartShift;
