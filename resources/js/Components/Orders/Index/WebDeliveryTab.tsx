import { Link } from '@inertiajs/react'
import { Badge, Col, Empty, Row, Typography } from 'antd'
import { PhoneOutlined } from '@ant-design/icons'
import { Order } from '@/types'

interface WebDeliveryProps {
  orders: Order[]
}

export default function WebDeliveryTab({ orders }: WebDeliveryProps) {
  const sortedOrders = [...orders].sort((a, b) => {
    return a.order_number > b.order_number ? -1 : 1
  })

  if (sortedOrders.length === 0) {
    return (
      <Empty
        className="mt-8"
        image={Empty.PRESENTED_IMAGE_SIMPLE}
        description="لا يوجد طلبات"
      />
    )
  }

  return (
    <Row gutter={[24, 16]}>
      {sortedOrders.map((order) => (
        <WebDeliveryOrder key={order.id} order={order} />
      ))}
    </Row>
  )
}

interface WebDeliveryOrderProps {
  order: Order
}

const WebDeliveryOrder = ({ order }: WebDeliveryOrderProps) => {
  const getOrderStatus = (status: string) => {
    const statusConfig = {
      pending: { color: 'orange', text: 'في الإنتظار' },
      processing: { color: 'blue', text: 'قيد التشغيل' },
      out_for_delivery: { color: 'purple', text: 'في طريق التوصيل' },
      completed: { color: 'green', text: 'مكتمل' },
      cancelled: { color: 'red', text: 'ملغي' },
    }

    return statusConfig[status as keyof typeof statusConfig] || { color: 'gray', text: status }
  }

  const statusInfo = getOrderStatus(order.status)

  return (
    <Col span={6}>
      <Link href={`/web-orders/manage-web-order/${order.id}`}>
        <Badge.Ribbon color={statusInfo.color} text={statusInfo.text}>
          <div className="isolate grid place-items-center gap-4 rounded-sm border border-gray-200 p-4 hover:shadow-lg transition-shadow">
            <Typography.Title level={4} className="mb-0">
              # طلب رقم {order.order_number}
            </Typography.Title>
            <Typography.Title className="flex items-center gap-2 mb-0 text-amber-600" level={5}>
              <PhoneOutlined size={16} />
              رقم العميل {order.customer?.phone || 'غير معروف'}
            </Typography.Title>
          </div>
        </Badge.Ribbon>
      </Link>
    </Col>
  )
}
