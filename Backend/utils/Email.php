<?php
/**
 * Email Utility Class
 * Handles sending emails for notifications
 */

class Email {
    /**
     * Send email using PHP's mail function
     */
    public static function send($to, $subject, $message, $headers = null) {
        // Default headers
        if (!$headers) {
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
            $headers .= "From: noreply@pshoesstore.com" . "\r\n";
        }

        // Send email
        return mail($to, $subject, $message, $headers);
    }

    /**
     * Send new order notification to owner
     */
    public static function sendNewOrderNotification($ownerEmail, $orderData, $customerData, $orderItems) {
        $subject = "🎉 New Order Received - Order #{$orderData['id']}";
        
        $productsList = '';
        foreach ($orderItems as $item) {
            $productsList .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>₨" . number_format($item['price'] * $item['quantity'], 2) . "</td>
                </tr>
            ";
        }

        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #ff9800; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 20px; }
                    .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    .amount { font-size: 18px; font-weight: bold; color: #ff9800; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>New Order Received!</h2>
                    </div>
                    <div class='content'>
                        <p>Hello,</p>
                        <p>A new order has been placed on P&S Online Shoes.</p>
                        
                        <h3>Order Details:</h3>
                        <table style='border: 1px solid #ddd;'>
                            <tr style='background: #f0f0f0;'>
                                <td style='padding: 10px; font-weight: bold;'>Order ID:</td>
                                <td style='padding: 10px;'>#" . $orderData['id'] . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; font-weight: bold;'>Order Number:</td>
                                <td style='padding: 10px;'>" . $orderData['order_number'] . "</td>
                            </tr>
                            <tr style='background: #f0f0f0;'>
                                <td style='padding: 10px; font-weight: bold;'>Date:</td>
                                <td style='padding: 10px;'>" . date('F j, Y g:i A', strtotime($orderData['created_at'])) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; font-weight: bold;'>Payment Method:</td>
                                <td style='padding: 10px;'>" . strtoupper(str_replace('-', ' ', $orderData['payment_method'])) . "</td>
                            </tr>
                        </table>

                        <h3>Customer Information:</h3>
                        <table style='border: 1px solid #ddd;'>
                            <tr style='background: #f0f0f0;'>
                                <td style='padding: 10px; font-weight: bold;'>Name:</td>
                                <td style='padding: 10px;'>" . $customerData['first_name'] . " " . $customerData['last_name'] . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; font-weight: bold;'>Email:</td>
                                <td style='padding: 10px;'><a href='mailto:" . $customerData['email'] . "'>" . $customerData['email'] . "</a></td>
                            </tr>
                            <tr style='background: #f0f0f0;'>
                                <td style='padding: 10px; font-weight: bold;'>Shipping Address:</td>
                                <td style='padding: 10px;'>" . $orderData['shipping_address'] . "</td>
                            </tr>
                        </table>

                        <h3>Products Ordered:</h3>
                        <table style='border: 1px solid #ddd;'>
                            <thead>
                                <tr style='background: #ff9800; color: white;'>
                                    <th style='padding: 10px; text-align: left;'>Product</th>
                                    <th style='padding: 10px; text-align: center;'>Quantity</th>
                                    <th style='padding: 10px; text-align: right;'>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                " . $productsList . "
                                <tr style='background: #f0f0f0; font-weight: bold;'>
                                    <td colspan='2' style='padding: 10px; text-align: right;'>Total Amount:</td>
                                    <td style='padding: 10px; text-align: right; color: #ff9800;'>₨" . number_format($orderData['total_amount'], 2) . "</td>
                                </tr>
                            </tbody>
                        </table>

                        <p style='color: #666; font-size: 14px;'>Please log in to your dashboard to manage this order.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2026 P&S Online Shoes. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        return self::send($ownerEmail, $subject, $message);
    }

    /**
     * Send order confirmation email to customer
     */
    public static function sendOrderConfirmation($customerEmail, $customerName, $orderData, $orderItems) {
        $subject = "Order Confirmation - P&S Online Shoes (Order #{$orderData['id']})";
        
        $productsList = '';
        foreach ($orderItems as $item) {
            $productsList .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>₨" . number_format($item['price'] * $item['quantity'], 2) . "</td>
                </tr>
            ";
        }

        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #00bcd4; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 20px; }
                    .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    .amount { font-size: 18px; font-weight: bold; color: #00bcd4; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Thank You for Your Order!</h2>
                    </div>
                    <div class='content'>
                        <p>Hello " . $customerName . ",</p>
                        <p>Your order has been successfully placed. Here are your order details:</p>
                        
                        <h3>Order Information:</h3>
                        <table style='border: 1px solid #ddd;'>
                            <tr style='background: #f0f0f0;'>
                                <td style='padding: 10px; font-weight: bold;'>Order ID:</td>
                                <td style='padding: 10px;'>#" . $orderData['id'] . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; font-weight: bold;'>Order Number:</td>
                                <td style='padding: 10px;'>" . $orderData['order_number'] . "</td>
                            </tr>
                            <tr style='background: #f0f0f0;'>
                                <td style='padding: 10px; font-weight: bold;'>Date:</td>
                                <td style='padding: 10px;'>" . date('F j, Y g:i A', strtotime($orderData['created_at'])) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; font-weight: bold;'>Payment Method:</td>
                                <td style='padding: 10px;'>" . strtoupper(str_replace('-', ' ', $orderData['payment_method'])) . "</td>
                            </tr>
                        </table>

                        <h3>Your Order Items:</h3>
                        <table style='border: 1px solid #ddd;'>
                            <thead>
                                <tr style='background: #00bcd4; color: white;'>
                                    <th style='padding: 10px; text-align: left;'>Product</th>
                                    <th style='padding: 10px; text-align: center;'>Quantity</th>
                                    <th style='padding: 10px; text-align: right;'>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                " . $productsList . "
                                <tr style='background: #f0f0f0; font-weight: bold;'>
                                    <td colspan='2' style='padding: 10px; text-align: right;'>Total Amount:</td>
                                    <td style='padding: 10px; text-align: right; color: #00bcd4;'>₨" . number_format($orderData['total_amount'], 2) . "</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>Shipping Address:</h3>
                        <p>" . $orderData['shipping_address'] . "</p>

                        <p style='color: #666; font-size: 14px;'>
                            We will process your order soon and notify you with shipping details. 
                            You can track your order anytime by logging into your account.
                        </p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2026 P&S Online Shoes. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        return self::send($customerEmail, $subject, $message);
    }
}
?>
