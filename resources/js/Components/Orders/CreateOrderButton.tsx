import React from "react";
import { router } from "@inertiajs/react";
import { Button } from "antd";
import { PlusOutlined, ShoppingCartOutlined, CarOutlined } from "@ant-design/icons";

interface CreateOrderButtonProps {
    orderType: "takeaway" | "delivery";
}

const buttonConfig = {
    takeaway: {
        icon: <ShoppingCartOutlined />,
        title: "إنشاء طلب تيك اواي",
    },
    delivery: {
        icon: <CarOutlined />,
        title: "إنشاء طلب ديلفري",
    }
};

export const CreateOrderButton: React.FC<CreateOrderButtonProps> = ({ orderType }) => {
    const config = buttonConfig[orderType];

    const createOrder = () => {
        router.post(route("orders.store"), { type: orderType });
    };

    return (
        <Button
            onClick={createOrder}
            className={`h-32 w-full`}
            size="large"
        >
            <div className="flex flex-col items-center justify-center gap-2">
                <div className="flex items-center gap-2 text-xl">
                    <PlusOutlined />
                    {config.icon}
                </div>
                <span>{config.title}</span>
            </div>
        </Button>
    );
};
