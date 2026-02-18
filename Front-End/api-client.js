// API Client for Online Shoes Store
const API_BASE_URL = '/Online-Shoes-Store/Backend/api';

// Products API
const Products = {
    async getAll(limit = 100, offset = 0) {
        try {
            const response = await fetch(`${API_BASE_URL}/products.php?request=list&limit=${limit}&offset=${offset}`, {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error fetching products:', error);
            return { success: false, message: error.message };
        }
    },

    async getById(id) {
        try {
            const response = await fetch(`${API_BASE_URL}/products.php?request=get&id=${id}`, {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error fetching product:', error);
            return { success: false, message: error.message };
        }
    },

    async create(data) {
        try {
            const response = await fetch(`${API_BASE_URL}/products.php?request=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Error creating product:', error);
            return { success: false, message: error.message };
        }
    },

    async update(id, data) {
        try {
            const response = await fetch(`${API_BASE_URL}/products.php?request=update/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Error updating product:', error);
            return { success: false, message: error.message };
        }
    },

    async delete(id) {
        try {
            const response = await fetch(`${API_BASE_URL}/products.php?request=delete/${id}`, {
                method: 'DELETE',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error deleting product:', error);
            return { success: false, message: error.message };
        }
    }
};

// Orders API
const Orders = {
    async getMyOrders(limit = 10, offset = 0) {
        try {
            const response = await fetch(`${API_BASE_URL}/orders.php?request=list&limit=${limit}&offset=${offset}`, {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error fetching orders:', error);
            return { success: false, message: error.message };
        }
    },

    async getOrderById(id) {
        try {
            const response = await fetch(`${API_BASE_URL}/orders.php?request=get&id=${id}`, {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error fetching order:', error);
            return { success: false, message: error.message };
        }
    },

    async create(data) {
        try {
            const response = await fetch(`${API_BASE_URL}/orders.php?request=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Error creating order:', error);
            return { success: false, message: error.message };
        }
    },

    async updateStatus(id, status) {
        try {
            const response = await fetch(`${API_BASE_URL}/orders.php?request=update-status&id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ status })
            });
            return await response.json();
        } catch (error) {
            console.error('Error updating order status:', error);
            return { success: false, message: error.message };
        }
    }
};

// Cart API
const Cart = {
    async add(product_id, quantity = 1) {
        try {
            const response = await fetch(`${API_BASE_URL}/cart.php?request=add`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ product_id, quantity })
            });
            return await response.json();
        } catch (error) {
            console.error('Error adding to cart:', error);
            return { success: false, message: error.message };
        }
    },

    async getCart() {
        try {
            const response = await fetch(`${API_BASE_URL}/cart.php?request=list`, {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error fetching cart:', error);
            return { success: false, message: error.message };
        }
    },

    async remove(product_id) {
        try {
            const response = await fetch(`${API_BASE_URL}/cart.php?request=remove&id=${product_id}`, {
                method: 'DELETE',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error removing from cart:', error);
            return { success: false, message: error.message };
        }
    }
};

// Payments API
const Payments = {
    async create(data) {
        try {
            const response = await fetch(`${API_BASE_URL}/payments.php?request=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Error creating payment:', error);
            return { success: false, message: error.message };
        }
    },

    async getByOrderId(order_id) {
        try {
            const response = await fetch(`${API_BASE_URL}/payments.php?request=get-by-order&order_id=${order_id}`, {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error fetching payment:', error);
            return { success: false, message: error.message };
        }
    },

    async uploadScreenshot(formData) {
        try {
            const response = await fetch(`${API_BASE_URL}/payments.php?request=upload-screenshot`, {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('Error uploading screenshot:', error);
            return { success: false, message: error.message };
        }
    }
};

// Delivery API
const Delivery = {
    async create(data) {
        try {
            const response = await fetch(`${API_BASE_URL}/delivery.php?request=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Error creating delivery:', error);
            return { success: false, message: error.message };
        }
    },

    async getByOrderId(order_id) {
        try {
            const response = await fetch(`${API_BASE_URL}/delivery.php?request=get-by-order&order_id=${order_id}`, {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error fetching delivery:', error);
            return { success: false, message: error.message };
        }
    },

    async updateStatus(id, delivery_status, tracking_number = '', actual_delivery_date = '') {
        try {
            const response = await fetch(`${API_BASE_URL}/delivery.php?request=update-status&id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ delivery_status, tracking_number, actual_delivery_date })
            });
            return await response.json();
        } catch (error) {
            console.error('Error updating delivery status:', error);
            return { success: false, message: error.message };
        }
    }
};

// Reviews API
const Reviews = {
    async create(data) {
        try {
            const response = await fetch(`${API_BASE_URL}/reviews.php?request=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Error creating review:', error);
            return { success: false, message: error.message };
        }
    },

    async getByProductId(product_id) {
        try {
            const response = await fetch(`${API_BASE_URL}/reviews.php?request=get-product-reviews&product_id=${product_id}`, {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error fetching reviews:', error);
            return { success: false, message: error.message };
        }
    },

    async getAll(limit = 100, offset = 0) {
        try {
            const response = await fetch(`${API_BASE_URL}/reviews.php?request=list&limit=${limit}&offset=${offset}`, {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error fetching reviews:', error);
            return { success: false, message: error.message };
        }
    },

    async update(id, data) {
        try {
            const response = await fetch(`${API_BASE_URL}/reviews.php?request=update&id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Error updating review:', error);
            return { success: false, message: error.message };
        }
    }
};

// Auth API
const Auth = {
    async login(email, password) {
        try {
            const response = await fetch(`${API_BASE_URL}/auth.php?request=login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ email, password })
            });
            return await response.json();
        } catch (error) {
            console.error('Error logging in:', error);
            return { success: false, message: error.message };
        }
    },

    async register(data) {
        try {
            const response = await fetch(`${API_BASE_URL}/auth.php?request=register`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Error registering:', error);
            return { success: false, message: error.message };
        }
    },

    async logout() {
        try {
            const response = await fetch(`${API_BASE_URL}/auth.php?request=logout`, {
                method: 'POST',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Error logging out:', error);
            return { success: false, message: error.message };
        }
    }
};
