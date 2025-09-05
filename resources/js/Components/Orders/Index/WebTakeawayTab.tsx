import { Col, Empty, Row } from 'antd'
import { OrderCard } from '@/Components/Orders/OrderCard'
import { Order } from '@/types'

interface WebTakeawayProps {
  orders: Order[]
}

export default function WebTakeawayTab({ orders }: WebTakeawayProps) {
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
        <Col span={6} key={order.id}>
          <OrderCard
            order={order}
            href={`/web-orders/manage-web-order/${order.id}`}
            orderType="web-takeaway"
          />
        </Col>
      ))}
    </Row>
  )
}
