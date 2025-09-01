import IsAdmin from "@/Components/IsAdmin";
import { User } from "@/types";
import {
    DollarCircleOutlined,
    LogoutOutlined,
    UserOutlined,
    BellOutlined,
} from "@ant-design/icons";
import { router, usePage } from "@inertiajs/react";
import {
    FloatButton,
    Layout,
    Typography,
    message,
    notification,
    Button,
    ConfigProvider,
} from "antd";
import { ReactNode, useLayoutEffect, useEffect } from "react";

const { Header, Content } = Layout;
const { Title } = Typography;

interface CashierLayoutProps {
    children: ReactNode;
    title?: string;
}

export default function CashierLayout({ children, title }: CashierLayoutProps) {
    const { auth } = usePage().props;
    const user = auth.user as User;

    const logout = () => {
        router.post("/logout", undefined, {
            onFinish: () => {
                window.location.href = "/admin/login";
            },
        });
    };

    const openCashierDrawer = () => {
        router.post("/open-cashier-drawer");
    };

    const toAdmin = () => {
        window.location.href = "/admin";
    };

    const page = usePage();
    useLayoutEffect(() => {
        if (Object.values(page.props.errors).length > 0) {
            message.destroy();
            Object.values(page.props.errors).forEach((error) => {
                message.error(error);
            });
        }
    }, [page]);

    // Web order notification listener
    useEffect(() => {
        // Request notification permission
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }

        if (!window.Echo) {
            console.log("Echo not available");
            return;
        }

        console.log("Setting up Echo listener...");

        // Listen for web order notifications
        const channel = window.Echo.private("web-orders")
            .listen(".web-order.received", (data: any) => {
                console.log("Web order received:", data);

                const order = data.order;

                // Play notification sound
                const audio = new Audio("/audio/web-notification.wav");
                audio
                    .play()
                    .catch((err) => console.log("Audio play failed:", err));

                // Show browser notification
                if (
                    "Notification" in window &&
                    Notification.permission === "granted"
                ) {
                    const browserNotification = new Notification(
                        "طلب أونلاين جديد",
                        {
                            body: `رقم الطلب: ${order.order_number} - ${order.typeString}`,
                            icon: "/images/logo.jpg",
                        }
                    );

                    browserNotification.onclick = () => {
                        window.focus();
                        notification.destroy();
                        router.get(`/web-orders/manage-web-order/${order.id}`);
                    };
                }

                // Show in-app notification
                notification.open({
                    message: "طلب أونلاين جديد",
                    description: (
                        <div className="flex flex-col gap-2">
                            <Typography.Text>
                                رقم الطلب: {order.order_number}
                            </Typography.Text>
                            <Typography.Text>
                                نوع الطلب: {order.typeString}
                            </Typography.Text>
                            <Typography.Text>
                                العميل: {order.customer_name}
                            </Typography.Text>
                            <Typography.Text>
                                الإجمالي: {order.total} جنيه
                            </Typography.Text>
                            <Button
                                type="primary"
                                size="small"
                                onClick={() => {
                                    notification.destroy();
                                    router.get(
                                        `/web-orders/manage-web-order/${order.id}`
                                    );
                                }}
                            >
                                عرض الطلب
                            </Button>
                        </div>
                    ),
                    duration: 0, // Don't auto close
                    placement: "topLeft",
                    icon: <BellOutlined style={{ color: "#1890ff" }} />,
                });
            })
            .listen(".test-event", (data: any) => {
                console.log("Test event received:", data);
                notification.info({
                    message: "Test Event Received",
                    description: JSON.stringify(data),
                });
            })
            .listenForWhisper("test", (data: any) => {
                console.log("Whisper received:", data);
            });

        // Listen for any other events for debugging
        channel.on("pusher:subscription_succeeded", () => {
            console.log("Successfully subscribed to web-orders channel");
        });

        // Listen for all events for debugging
        channel.on("pusher:internal:connection_established", () => {
            console.log("Connection established");
        });

        // Cleanup function
        return () => {
            if (channel) {
                window.Echo.leave("web-orders");
            }
        };
    }, []);

    useEffect(() => {
        // refetch the page when the user clicks the back button
        const handlePopState = (event: PopStateEvent) => {
            event.stopImmediatePropagation();
            if (window.location.href.includes("admin")) {
                window.location.reload();
                return;
            }
            router.reload({
                replace: true,
            });
        };
        window.addEventListener("popstate", handlePopState);
        return () => {
            window.removeEventListener("popstate", handlePopState);
        };
    }, []);

    return (
        <Layout className="min-h-screen" dir="rtl">
            <FloatButton.Group shape="circle" style={{ left: 24 }}>
                <IsAdmin>
                    <FloatButton
                        tooltip="الادارة"
                        icon={<UserOutlined />}
                        onClick={() => toAdmin()}
                    />
                </IsAdmin>
                <FloatButton
                    tooltip="فتح درج الكاشير"
                    icon={<DollarCircleOutlined />}
                    onClick={() => openCashierDrawer()}
                />
                <FloatButton
                    tooltip="تسجيل الخروج"
                    icon={<LogoutOutlined />}
                    onClick={logout}
                />
            </FloatButton.Group>

            <Content className="bg-gray-50">
                <ConfigProvider
                    direction="rtl"
                    theme={{
                        token: {
                            colorPrimary: "#7E57C2",
                            colorError: "#cf6679",
                            fontSize: 18,
                            // fontFamily: "tajawal",
                            // lineHeight: 1,
                        },
                    }}
                >
                    {children}
                </ConfigProvider>
            </Content>
        </Layout>
    );
}
