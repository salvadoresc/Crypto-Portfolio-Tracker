// Crypto Portfolio Tracker - Dashboard React con Recharts completo
(function($) {
    'use strict';

    // Verificar que tenemos las dependencias necesarias
    if (!window.wp || !window.wp.element || !window.cptAjax) {
        console.error('CPT: Dependencias faltantes');
        return;
    }

    const { createElement: h, useState, useEffect, useMemo } = wp.element;
    const { apiFetch } = wp;

    // Hook personalizado para la API
    const useAPI = () => {
        const makeRequest = async (endpoint, options = {}) => {
            try {
                const response = await apiFetch({
                    path: `/crypto-portfolio/v1${endpoint}`,
                    method: options.method || 'GET',
                    data: options.data || null,
                    headers: {
                        'X-WP-Nonce': cptAjax.nonce,
                        'Content-Type': 'application/json',
                    }
                });
                return response;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        };

        return { makeRequest };
    };

    // Hook para datos del portfolio
    const usePortfolio = () => {
        const [portfolio, setPortfolio] = useState([]);
        const [transactions, setTransactions] = useState([]);
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        const { makeRequest } = useAPI();

        const loadPortfolio = async () => {
            try {
                setLoading(true);
                
                const [portfolioData, transactionsData] = await Promise.all([
                    makeRequest('/portfolio'),
                    makeRequest('/transactions?limit=100')
                ]);

                console.log('Portfolio data received:', portfolioData);
                console.log('Transactions data received:', transactionsData);

                setPortfolio(portfolioData || []);
                setTransactions(transactionsData || []);
                setError(null);
            } catch (err) {
                console.error('Error loading portfolio:', err);
                setError(err.message || 'Error al cargar datos');
                setPortfolio([]);
                setTransactions([]);
            } finally {
                setLoading(false);
            }
        };

        const addTransaction = async (transactionData) => {
            try {
                console.log('Enviando transacción:', transactionData);
                await makeRequest('/transactions', {
                    method: 'POST',
                    data: transactionData
                });
                await loadPortfolio();
            } catch (err) {
                setError(err.message);
                throw err;
            }
        };

        const updateTransaction = async (transactionId, transactionData) => {
            try {
                console.log('Actualizando transacción:', transactionId, transactionData);
                await makeRequest(`/transactions/${transactionId}`, {
                    method: 'PUT',
                    data: transactionData
                });
                await loadPortfolio();
            } catch (err) {
                setError(err.message);
                throw err;
            }
        };

        const deleteTransaction = async (transactionId) => {
            try {
                await makeRequest(`/transactions/${transactionId}`, {
                    method: 'DELETE'
                });
                await loadPortfolio();
            } catch (err) {
                setError(err.message);
                throw err;
            }
        };

        useEffect(() => {
            if (cptAjax.isLoggedIn) {
                loadPortfolio();
            }
        }, []);

        return { 
            portfolio, 
            transactions, 
            loading, 
            error, 
            addTransaction,
            updateTransaction, 
            deleteTransaction,
            reload: loadPortfolio 
        };
    };

    // Componente de estadísticas
    const StatsCard = ({ title, value, icon, color = 'blue' }) => {
        return h('div', {
            className: `cpt-stats-card ${color} cpt-fade-in`
        }, [
            h('div', { 
                key: 'content',
                className: 'cpt-flex cpt-items-center cpt-justify-between' 
            }, [
                h('div', { key: 'info' }, [
                    h('p', { 
                        key: 'title',
                        className: `cpt-stat-label ${color}` 
                    }, title),
                    h('p', { 
                        key: 'value',
                        className: 'cpt-stat-value' 
                    }, value)
                ]),
                h('div', { 
                    key: 'icon',
                    className: `cpt-stat-icon ${color}` 
                }, icon)
            ])
        ]);
    };

    // Componente de formulario para añadir/editar transacciones
    const TransactionForm = ({ onSubmit, onCancel, editingTransaction = null }) => {
        const [formData, setFormData] = useState(() => {
            if (editingTransaction) {
                // Para edición, usar los valores reales de la transacción
                const totalValue = parseFloat(editingTransaction.total_value || 0);
                const price = parseFloat(editingTransaction.price_per_coin || 0);
                const quantity = parseFloat(editingTransaction.amount || 0);
                
                return {
                    coin_id: editingTransaction.coin_id || '',
                    coin_symbol: editingTransaction.coin_symbol || '',
                    coin_name: editingTransaction.coin_name || '',
                    type: editingTransaction.transaction_type || 'buy',
                    amount: totalValue.toString(),
                    price: price.toString(),
                    quantity: quantity.toString(),
                    date: editingTransaction.transaction_date ? editingTransaction.transaction_date.split(' ')[0] : new Date().toISOString().split('T')[0],
                    exchange: editingTransaction.exchange || '',
                    notes: editingTransaction.notes || ''
                };
            }
            
            return {
                coin_id: '',
                coin_symbol: '',
                coin_name: '',
                type: 'buy',
                amount: '',
                price: '',
                quantity: '',
                date: new Date().toISOString().split('T')[0],
                exchange: '',
                notes: ''
            };
        });

        const [coinSuggestions, setCoinSuggestions] = useState([]);
        const [showSuggestions, setShowSuggestions] = useState(false);
        const { makeRequest } = useAPI();

        const searchCoins = async (query) => {
            if (query.length < 2) {
                setCoinSuggestions([]);
                return;
            }

            try {
                const results = await makeRequest(`/market/search?q=${encodeURIComponent(query)}`);
                setCoinSuggestions(results || []);
                setShowSuggestions(true);
            } catch (error) {
                console.error('Search error:', error);
            }
        };

        const selectCoin = (coin) => {
            setFormData({
                ...formData,
                coin_id: coin.id,
                coin_symbol: coin.symbol,
                coin_name: coin.name
            });
            setShowSuggestions(false);
            setCoinSuggestions([]);
        };

        const handleSubmit = (e) => {
            e.preventDefault();
            
            if (!formData.coin_symbol || !formData.amount || !formData.price || !formData.quantity) {
                alert('Por favor completa todos los campos requeridos');
                return;
            }

            const amount = parseFloat(formData.amount);
            const price = parseFloat(formData.price);
            const quantity = parseFloat(formData.quantity);

            if (amount <= 0 || price <= 0 || quantity <= 0) {
                alert('El monto, precio y cantidad deben ser mayores a 0');
                return;
            }

            const submitData = {
                coin_id: formData.coin_id || formData.coin_symbol.toLowerCase(),
                coin_symbol: formData.coin_symbol.toUpperCase(),
                coin_name: formData.coin_name || formData.coin_symbol,
                type: formData.type,
                amount: quantity,  // Cantidad exacta ingresada por el usuario
                price: price,      // Precio por unidad
                total: amount,     // Monto total invertido (puede incluir comisiones)
                date: formData.date,
                exchange: formData.exchange,
                notes: formData.notes
            };

            console.log('Datos del formulario a enviar:', submitData);
            onSubmit(submitData);
        };

        const updateFormData = (field, value) => {
            setFormData(prev => ({ ...prev, [field]: value }));
        };

        return h('div', {
            className: 'cpt-glass-card cpt-fade-in'
        }, [
            h('h3', { 
                key: 'title',
                className: 'cpt-text-xl cpt-font-bold cpt-text-white cpt-mb-4' 
            }, editingTransaction ? 'Editar Transacción' : 'Añadir Transacción'),
            
            h('form', { 
                key: 'form',
                onSubmit: handleSubmit,
                className: 'cpt-space-y-4' 
            }, [
                // Campo de crypto
                h('div', { 
                    key: 'crypto-field',
                    style: { position: 'relative' }
                }, [
                    h('label', { 
                        key: 'label',
                        className: 'cpt-text-white cpt-font-medium cpt-mb-2',
                        style: { display: 'block' }
                    }, 'Criptomoneda'),
                    h('input', {
                        key: 'input',
                        type: 'text',
                        placeholder: 'Ej: BTC, Bitcoin',
                        value: formData.coin_symbol || formData.coin_name,
                        onChange: (e) => {
                            const value = e.target.value.toUpperCase();
                            updateFormData('coin_symbol', value);
                            updateFormData('coin_name', value);
                            if (!editingTransaction) searchCoins(value);
                        },
                        className: 'cpt-input',
                        required: true
                    }),
                    
                    // Dropdown de sugerencias
                    showSuggestions && coinSuggestions.length > 0 && h('div', {
                        key: 'suggestions',
                        style: {
                            position: 'absolute',
                            top: '100%',
                            left: 0,
                            right: 0,
                            zIndex: 50,
                            background: 'rgba(30, 41, 59, 0.95)',
                            border: '1px solid rgba(139, 92, 246, 0.3)',
                            borderRadius: '0.5rem',
                            marginTop: '0.25rem',
                            maxHeight: '12rem',
                            overflowY: 'auto'
                        }
                    }, coinSuggestions.map((coin, idx) => 
                        h('div', {
                            key: coin.id || idx,
                            onClick: () => selectCoin(coin),
                            style: {
                                padding: '0.75rem 1rem',
                                cursor: 'pointer',
                                display: 'flex',
                                alignItems: 'center',
                                gap: '0.5rem',
                                color: 'white'
                            },
                            onMouseEnter: (e) => e.target.style.background = 'rgba(139, 92, 246, 0.2)',
                            onMouseLeave: (e) => e.target.style.background = 'transparent'
                        }, [
                            coin.thumb && h('img', {
                                key: 'thumb',
                                src: coin.thumb,
                                alt: coin.symbol,
                                style: { width: '1.5rem', height: '1.5rem', borderRadius: '50%' }
                            }),
                            h('span', { key: 'text' }, `${coin.name} (${coin.symbol})`)
                        ])
                    ))
                ]),

                // Tipo y Fecha
                h('div', { 
                    key: 'type-date',
                    className: 'cpt-grid cpt-grid-cols-2 cpt-gap-4' 
                }, [
                    h('div', { key: 'type' }, [
                        h('label', { 
                            className: 'cpt-text-white cpt-font-medium cpt-mb-2',
                            style: { display: 'block' }
                        }, 'Tipo'),
                        h('select', {
                            value: formData.type,
                            onChange: (e) => updateFormData('type', e.target.value),
                            className: 'cpt-select'
                        }, [
                            h('option', { key: 'buy', value: 'buy' }, 'Compra'),
                            h('option', { key: 'sell', value: 'sell' }, 'Venta')
                        ])
                    ]),
                    h('div', { key: 'date' }, [
                        h('label', { 
                            className: 'cpt-text-white cpt-font-medium cpt-mb-2',
                            style: { display: 'block' }
                        }, 'Fecha'),
                        h('input', {
                            type: 'date',
                            value: formData.date,
                            onChange: (e) => updateFormData('date', e.target.value),
                            className: 'cpt-input',
                            required: true
                        })
                    ])
                ]),

                // Precio y Cantidad (campos separados e independientes)
                h('div', { 
                    key: 'price-quantity',
                    className: 'cpt-grid cpt-grid-cols-2 cpt-gap-4' 
                }, [
                    h('div', { key: 'price' }, [
                        h('label', { 
                            className: 'cpt-text-white cpt-font-medium cpt-mb-2',
                            style: { display: 'block' }
                        }, 'Precio por Unidad ($)'),
                        h('input', {
                            type: 'number',
                            step: '0.00000001',
                            placeholder: '50000.00',
                            value: formData.price,
                            onChange: (e) => updateFormData('price', e.target.value),
                            className: 'cpt-input',
                            required: true
                        }),
                        h('small', {
                            style: { color: '#9ca3af', fontSize: '0.75rem' }
                        }, 'Precio de la crypto en ese momento')
                    ]),
                    h('div', { key: 'quantity' }, [
                        h('label', { 
                            className: 'cpt-text-white cpt-font-medium cpt-mb-2',
                            style: { display: 'block' }
                        }, 'Cantidad Exacta Recibida'),
                        h('input', {
                            type: 'number',
                            step: '0.00000001',
                            placeholder: '0.00043700',
                            value: formData.quantity,
                            onChange: (e) => updateFormData('quantity', e.target.value),
                            className: 'cpt-input',
                            required: true
                        }),
                        h('small', {
                            style: { color: '#9ca3af', fontSize: '0.75rem' }
                        }, 'Cantidad exacta que recibiste (según tu exchange)')
                    ])
                ]),

                // Monto total (calculado automáticamente o manual)
                h('div', { key: 'total-amount' }, [
                    h('label', { 
                        className: 'cpt-text-white cpt-font-medium cpt-mb-2',
                        style: { display: 'block' }
                    }, 'Monto Total Invertido ($)'),
                    h('input', {
                        type: 'number',
                        step: '0.01',
                        placeholder: '100.00',
                        value: formData.amount,
                        onChange: (e) => updateFormData('amount', e.target.value),
                        className: 'cpt-input',
                        required: true
                    }),
                    h('small', {
                        style: { color: '#9ca3af', fontSize: '0.75rem' }
                    }, 'Monto total que gastaste (incluyendo comisiones)')
                ]),

                // Mostrar cálculo de verificación
                (formData.price && formData.quantity) && h('div', {
                    key: 'verification-calc',
                    style: {
                        background: 'rgba(139, 92, 246, 0.1)',
                        padding: '0.75rem',
                        borderRadius: '0.5rem',
                        border: '1px solid rgba(139, 92, 246, 0.3)'
                    }
                }, [
                    h('div', { style: { color: '#c4b5fd', fontSize: '0.875rem' } }, [
                        h('strong', {}, 'Verificación: '),
                        h('span', { style: { color: '#10b981' } }, 
                          `${formData.quantity} × $${formData.price} = $${(parseFloat(formData.quantity || 0) * parseFloat(formData.price || 0)).toFixed(2)}`),
                        h('div', { style: { fontSize: '0.75rem', marginTop: '0.25rem', opacity: 0.8 } }, 
                          `💡 El monto total puede ser diferente por comisiones del exchange`)
                    ])
                ]),

                // Exchange
                h('div', { key: 'exchange' }, [
                    h('label', { 
                        className: 'cpt-text-white cpt-font-medium cpt-mb-2',
                        style: { display: 'block' }
                    }, 'Exchange (opcional)'),
                    h('input', {
                        type: 'text',
                        placeholder: 'Binance, Coinbase, etc.',
                        value: formData.exchange,
                        onChange: (e) => updateFormData('exchange', e.target.value),
                        className: 'cpt-input'
                    })
                ]),

                // Notas
                h('div', { key: 'notes' }, [
                    h('label', { 
                        className: 'cpt-text-white cpt-font-medium cpt-mb-2',
                        style: { display: 'block' }
                    }, 'Notas (opcional)'),
                    h('textarea', {
                        placeholder: 'Notas adicionales...',
                        value: formData.notes,
                        onChange: (e) => updateFormData('notes', e.target.value),
                        className: 'cpt-textarea'
                    })
                ]),

                // Botones
                h('div', { 
                    key: 'buttons',
                    className: 'cpt-flex cpt-gap-4' 
                }, [
                    h('button', {
                        key: 'submit',
                        type: 'submit',
                        className: 'cpt-btn cpt-btn-primary'
                    }, editingTransaction ? 'Actualizar Transacción' : 'Añadir Transacción'),
                    h('button', {
                        key: 'cancel',
                        type: 'button',
                        onClick: onCancel,
                        className: 'cpt-btn cpt-btn-secondary'
                    }, 'Cancelar')
                ])
            ])
        ]);
    };

    // Componente principal del Dashboard
    const CryptoDashboard = () => {
        const { portfolio, transactions, loading, error, addTransaction, updateTransaction, deleteTransaction, reload } = usePortfolio();
        const [showTransactionForm, setShowTransactionForm] = useState(false);
        const [editingTransaction, setEditingTransaction] = useState(null);

        // Cálculos del portfolio
        const portfolioStats = useMemo(() => {
            if (!portfolio.length) {
                return {
                    totalInvested: 0,
                    totalValue: 0,
                    totalProfit: 0,
                    totalProfitPercent: 0
                };
            }

            console.log('Calculando stats con portfolio:', portfolio);

            let totalInvested = 0;
            let totalValue = 0;

            portfolio.forEach(item => {
                const invested = parseFloat(item.total_invested || 0);
                const amount = parseFloat(item.total_amount || 0);
                const currentPrice = parseFloat(item.current_price || 0);
                
                console.log(`Item ${item.coin_symbol}:`, {
                    invested,
                    amount,
                    currentPrice,
                    value: amount * currentPrice
                });
                
                totalInvested += invested;
                totalValue += (amount * currentPrice);
            });

            const totalProfit = totalValue - totalInvested;
            const totalProfitPercent = totalInvested > 0 ? (totalProfit / totalInvested) * 100 : 0;

            const stats = {
                totalInvested: totalInvested,
                totalValue: totalValue,
                totalProfit: totalProfit,
                totalProfitPercent: totalProfitPercent
            };

            console.log('Stats calculados:', stats);
            return stats;
        }, [portfolio]);

        // Datos para gráficos
        const chartData = useMemo(() => {
            if (!transactions.length) return { timelineData: [], distributionData: [], performanceData: [] };

            // 1. Timeline de inversiones acumulativas
            const sortedTx = [...transactions].sort((a, b) => new Date(a.transaction_date) - new Date(b.transaction_date));
            let cumulativeInvestment = 0;
            const timelineData = sortedTx.map(tx => {
                cumulativeInvestment += parseFloat(tx.total_value || 0);
                return {
                    date: tx.transaction_date ? tx.transaction_date.split(' ')[0] : '',
                    investment: cumulativeInvestment,
                    crypto: tx.coin_symbol,
                    amount: parseFloat(tx.total_value || 0)
                };
            });

            // 2. Distribución del portfolio (para pie chart)
            const colors = ['#8B5CF6', '#06B6D4', '#10B981', '#F59E0B', '#EF4444', '#8B5A2B', '#EC4899', '#6366F1'];
            const distributionData = portfolio.map((item, index) => {
                const amount = parseFloat(item.total_amount || 0);
                const currentPrice = parseFloat(item.current_price || 0);
                const currentValue = amount * currentPrice;
                const percentage = portfolioStats.totalValue > 0 ? (currentValue / portfolioStats.totalValue) * 100 : 0;
                
                return {
                    name: item.coin_symbol,
                    value: currentValue,
                    percentage: percentage,
                    fill: colors[index % colors.length]
                };
            });

            // 3. Performance por crypto (para bar chart)
            const performanceData = portfolio.map(item => {
                const invested = parseFloat(item.total_invested || 0);
                const amount = parseFloat(item.total_amount || 0);
                const currentPrice = parseFloat(item.current_price || 0);
                const currentValue = amount * currentPrice;
                const profit = currentValue - invested;
                const profitPercent = invested > 0 ? (profit / invested) * 100 : 0;

                return {
                    crypto: item.coin_symbol,
                    profitPercent: profitPercent,
                    invested: invested,
                    currentValue: currentValue,
                    profit: profit
                };
            });

            return { timelineData, distributionData, performanceData };
        }, [transactions, portfolio, portfolioStats]);

        const handleAddTransaction = async (transactionData) => {
            try {
                await addTransaction(transactionData);
                setShowTransactionForm(false);
            } catch (error) {
                alert('Error al añadir transacción: ' + error.message);
            }
        };

        const handleEditTransaction = async (transactionData) => {
            try {
                await updateTransaction(editingTransaction.id, transactionData);
                setEditingTransaction(null);
                setShowTransactionForm(false);
            } catch (error) {
                alert('Error al actualizar transacción: ' + error.message);
            }
        };

        const handleDeleteTransaction = async (transactionId) => {
            if (confirm('¿Estás seguro de que quieres eliminar esta transacción?')) {
                try {
                    await deleteTransaction(transactionId);
                } catch (error) {
                    alert('Error al eliminar transacción: ' + error.message);
                }
            }
        };

        const startEdit = (transaction) => {
            console.log('Editando transacción:', transaction);
            setEditingTransaction(transaction);
            setShowTransactionForm(true);
        };

        const cancelForm = () => {
            setShowTransactionForm(false);
            setEditingTransaction(null);
        };

        // Verificar si Recharts está disponible
        const hasRecharts = window.Recharts && window.Recharts.LineChart && window.Recharts.PieChart && window.Recharts.BarChart;

        // Pantalla de login
        if (!cptAjax.isLoggedIn) {
            return h('div', {
                className: 'cpt-dashboard-container cpt-flex cpt-items-center cpt-justify-center'
            }, [
                h('div', {
                    key: 'login-card',
                    className: 'cpt-glass-card cpt-text-center',
                    style: { maxWidth: '28rem' }
                }, [
                    h('h2', { 
                        key: 'title',
                        className: 'cpt-text-2xl cpt-font-bold cpt-text-white cpt-mb-4' 
                    }, 'Acceso Requerido'),
                    h('p', { 
                        key: 'description',
                        style: { color: '#c4b5fd', marginBottom: '1.5rem' }
                    }, 'Necesitas iniciar sesión para ver tu portfolio de criptomonedas.'),
                    h('div', { 
                        key: 'buttons',
                        className: 'cpt-flex cpt-gap-4 cpt-justify-center' 
                    }, [
                        h('a', {
                            key: 'login',
                            href: cptAjax.loginUrl || '#',
                            className: 'cpt-btn cpt-btn-primary'
                        }, 'Iniciar Sesión'),
                        h('a', {
                            key: 'register',
                            href: cptAjax.registerUrl || '#',
                            className: 'cpt-btn cpt-btn-secondary'
                        }, 'Registrarse')
                    ])
                ])
            ]);
        }

        // Pantalla de carga
        if (loading) {
            return h('div', {
                className: 'cpt-dashboard-container cpt-flex cpt-items-center cpt-justify-center'
            }, [
                h('div', {
                    key: 'loader',
                    className: 'cpt-text-center'
                }, [
                    h('div', { 
                        key: 'spinner',
                        className: 'cpt-loading-spinner',
                        style: { margin: '0 auto 1rem auto' }
                    }),
                    h('div', { 
                        key: 'text',
                        className: 'cpt-text-white cpt-text-xl' 
                    }, 'Cargando portfolio...')
                ])
            ]);
        }

        return h('div', {
            className: 'cpt-dashboard-container'
        }, [
            h('div', {
                key: 'container',
                className: 'cpt-max-width cpt-space-y-6'
            }, [
                // Header
                h('div', { 
                    key: 'header',
                    className: 'cpt-header' 
                }, [
                    h('h1', { 
                        key: 'title',
                        className: 'cpt-title' 
                    }, 'Dashboard de Inversiones Crypto'),
                    h('p', { 
                        key: 'subtitle',
                        className: 'cpt-subtitle' 
                    }, 'Análisis completo de tu portafolio crypto')
                ]),

                // Error handling
                error && h('div', {
                    key: 'error',
                    style: {
                        background: 'rgba(239, 68, 68, 0.2)',
                        border: '1px solid rgba(239, 68, 68, 0.5)',
                        borderRadius: '0.5rem',
                        padding: '1rem',
                        color: '#fecaca'
                    }
                }, `Error: ${error}`),

                // Actions
                h('div', { 
                    key: 'actions',
                    className: 'cpt-glass-card cpt-flex cpt-justify-between cpt-items-center'
                }, [
                    h('div', { 
                        key: 'buttons',
                        className: 'cpt-flex cpt-gap-4' 
                    }, [
                        h('button', {
                            key: 'add-transaction',
                            onClick: () => {
                                setEditingTransaction(null);
                                setShowTransactionForm(!showTransactionForm);
                            },
                            className: 'cpt-btn cpt-btn-primary'
                        }, showTransactionForm ? 'Cancelar' : 'Añadir Transacción'),
                        h('button', {
                            key: 'reload',
                            onClick: reload,
                            className: 'cpt-btn cpt-btn-secondary'
                        }, 'Actualizar Precios')
                    ])
                ]),

                // Transaction Form
                showTransactionForm && h(TransactionForm, {
                    key: 'transaction-form',
                    onSubmit: editingTransaction ? handleEditTransaction : handleAddTransaction,
                    onCancel: cancelForm,
                    editingTransaction: editingTransaction
                }),

                // Stats Cards
                h('div', { 
                    key: 'stats',
                    className: 'cpt-grid cpt-grid-4' 
                }, [
                    h(StatsCard, {
                        key: 'invested',
                        title: 'Inversión Total',
                        value: `${portfolioStats.totalInvested.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                        icon: '💰',
                        color: 'green'
                    }),
                    h(StatsCard, {
                        key: 'value',
                        title: 'Valor Actual',
                        value: `${portfolioStats.totalValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                        icon: '🎯',
                        color: 'blue'
                    }),
                    h(StatsCard, {
                        key: 'pnl',
                        title: 'P&L Total',
                        value: `${portfolioStats.totalProfit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                        icon: portfolioStats.totalProfit >= 0 ? '📈' : '📉',
                        color: portfolioStats.totalProfit >= 0 ? 'green' : 'red'
                    }),
                    h(StatsCard, {
                        key: 'roi',
                        title: 'ROI %',
                        value: `${portfolioStats.totalProfitPercent.toFixed(2)}%`,
                        icon: '📊',
                        color: portfolioStats.totalProfitPercent >= 0 ? 'green' : 'red'
                    })
                ]),

                // SECCIÓN DE GRÁFICOS - LOS 4 GRÁFICOS DEL DISEÑO ORIGINAL
                chartData.timelineData.length > 0 && hasRecharts && h('div', { 
                    key: 'charts-section',
                    className: 'cpt-grid cpt-grid-2' 
                }, [
                    // 1. Evolución de Inversiones
                    h('div', { 
                        key: 'timeline-chart',
                        className: 'cpt-glass-card' 
                    }, [
                        h('h3', { 
                            key: 'title',
                            className: 'cpt-text-xl cpt-font-bold cpt-text-white cpt-mb-4' 
                        }, 'Evolución de Inversiones'),
                        h('div', { 
                            key: 'chart-container',
                            style: { width: '100%', height: '300px' }
                        }, [
                            h(window.Recharts.ResponsiveContainer, { 
                                key: 'responsive',
                                width: '100%', 
                                height: '100%' 
                            }, [
                                h(window.Recharts.LineChart, { 
                                    key: 'line-chart',
                                    data: chartData.timelineData 
                                }, [
                                    h(window.Recharts.CartesianGrid, { 
                                        key: 'grid',
                                        strokeDasharray: '3 3', 
                                        stroke: '#374151' 
                                    }),
                                    h(window.Recharts.XAxis, { 
                                        key: 'x-axis',
                                        dataKey: 'date', 
                                        stroke: '#9CA3AF' 
                                    }),
                                    h(window.Recharts.YAxis, { 
                                        key: 'y-axis',
                                        stroke: '#9CA3AF' 
                                    }),
                                    h(window.Recharts.Tooltip, { 
                                        key: 'tooltip',
                                        contentStyle: { 
                                            backgroundColor: 'rgba(0,0,0,0.8)', 
                                            border: '1px solid #8B5CF6',
                                            borderRadius: '8px',
                                            color: 'white'
                                        }
                                    }),
                                    h(window.Recharts.Line, { 
                                        key: 'line',
                                        type: 'monotone', 
                                        dataKey: 'investment', 
                                        stroke: '#8B5CF6', 
                                        strokeWidth: 3,
                                        dot: { fill: '#8B5CF6' }
                                    })
                                ])
                            ])
                        ])
                    ]),

                    // 2. Distribución del Portfolio
                    h('div', { 
                        key: 'distribution-chart',
                        className: 'cpt-glass-card' 
                    }, [
                        h('h3', { 
                            key: 'title',
                            className: 'cpt-text-xl cpt-font-bold cpt-text-white cpt-mb-4' 
                        }, 'Distribución del Portfolio'),
                        chartData.distributionData.length > 0 ? h('div', { 
                            key: 'chart-container',
                            style: { width: '100%', height: '300px' }
                        }, [
                            h(window.Recharts.ResponsiveContainer, { 
                                key: 'responsive',
                                width: '100%', 
                                height: '100%' 
                            }, [
                                h(window.Recharts.PieChart, { key: 'pie-chart' }, [
                                    h(window.Recharts.Pie, {
                                        key: 'pie',
                                        data: chartData.distributionData,
                                        cx: '50%',
                                        cy: '50%',
                                        outerRadius: 100,
                                        fill: '#8884d8',
                                        dataKey: 'value',
                                        label: ({ name, percentage }) => `${name}: ${percentage.toFixed(1)}%`
                                    }),
                                    h(window.Recharts.Tooltip, { 
                                        key: 'tooltip',
                                        contentStyle: { 
                                            backgroundColor: 'rgba(0,0,0,0.8)', 
                                            border: '1px solid #8B5CF6',
                                            borderRadius: '8px',
                                            color: 'white'
                                        },
                                        formatter: (value) => [`${value.toFixed(2)}`, 'Valor']
                                    })
                                ])
                            ])
                        ]) : h('div', { 
                            key: 'no-data',
                            className: 'cpt-text-center cpt-text-white',
                            style: { padding: '2rem' }
                        }, 'Sin datos para mostrar')
                    ])
                ]),

                // 3. Performance por Crypto
                chartData.performanceData.length > 0 && hasRecharts && h('div', { 
                    key: 'performance-chart',
                    className: 'cpt-glass-card' 
                }, [
                    h('h3', { 
                        key: 'title',
                        className: 'cpt-text-xl cpt-font-bold cpt-text-white cpt-mb-4' 
                    }, 'Performance por Crypto'),
                    h('div', { 
                        key: 'chart-container',
                        style: { width: '100%', height: '300px' }
                    }, [
                        h(window.Recharts.ResponsiveContainer, { 
                            key: 'responsive',
                            width: '100%', 
                            height: '100%' 
                        }, [
                            h(window.Recharts.BarChart, { 
                                key: 'bar-chart',
                                data: chartData.performanceData 
                            }, [
                                h(window.Recharts.CartesianGrid, { 
                                    key: 'grid',
                                    strokeDasharray: '3 3', 
                                    stroke: '#374151' 
                                }),
                                h(window.Recharts.XAxis, { 
                                    key: 'x-axis',
                                    dataKey: 'crypto', 
                                    stroke: '#9CA3AF' 
                                }),
                                h(window.Recharts.YAxis, { 
                                    key: 'y-axis',
                                    stroke: '#9CA3AF' 
                                }),
                                h(window.Recharts.Tooltip, { 
                                    key: 'tooltip',
                                    contentStyle: { 
                                        backgroundColor: 'rgba(0,0,0,0.8)', 
                                        border: '1px solid #8B5CF6',
                                        borderRadius: '8px',
                                        color: 'white'
                                    },
                                    formatter: (value) => [`${value.toFixed(2)}%`, 'ROI']
                                }),
                                h(window.Recharts.Bar, { 
                                    key: 'bar',
                                    dataKey: 'profitPercent', 
                                    fill: '#8B5CF6' 
                                })
                            ])
                        ])
                    ])
                ]),

                // 4. Detalle por Crypto - TABLA EXPANDIDA
                portfolio.length > 0 ? 
                    h('div', { 
                        key: 'detail-table',
                        className: 'cpt-glass-card' 
                    }, [
                        h('h3', { 
                            key: 'title',
                            className: 'cpt-text-xl cpt-font-bold cpt-text-white cpt-mb-4' 
                        }, 'Detalle por Crypto'),
                        h('div', { 
                            key: 'table-container',
                            style: { overflowX: 'auto' }
                        }, [
                            h('table', { 
                                key: 'table',
                                className: 'cpt-table' 
                            }, [
                                h('thead', { key: 'thead' }, [
                                    h('tr', { key: 'header-row' }, [
                                        h('th', { key: 'crypto' }, 'Crypto'),
                                        h('th', { key: 'amount', className: 'text-right' }, 'Cantidad'),
                                        h('th', { key: 'invested', className: 'text-right' }, 'Invertido'),
                                        h('th', { key: 'avg-price', className: 'text-right' }, 'Precio Prom.'),
                                        h('th', { key: 'current-price', className: 'text-right' }, 'Precio Actual'),
                                        h('th', { key: 'current-value', className: 'text-right' }, 'Valor Actual'),
                                        h('th', { key: 'pnl', className: 'text-right' }, 'P&L'),
                                        h('th', { key: 'roi', className: 'text-right' }, 'ROI %')
                                    ])
                                ]),
                                h('tbody', { key: 'tbody' }, 
                                    portfolio.map((item, index) => {
                                        const amount = parseFloat(item.total_amount || 0);
                                        const invested = parseFloat(item.total_invested || 0);
                                        const avgPrice = parseFloat(item.avg_buy_price || 0);
                                        const currentPrice = parseFloat(item.current_price || 0);
                                        const currentValue = amount * currentPrice;
                                        const profit = currentValue - invested;
                                        const profitPercent = invested > 0 ? (profit / invested) * 100 : 0;

                                        return h('tr', { 
                                            key: item.coin_id || index
                                        }, [
                                            h('td', { key: 'symbol', className: 'cpt-font-bold' }, [
                                                h('div', {}, item.coin_symbol),
                                                h('small', { style: { color: '#9ca3af', display: 'block' } }, item.coin_name)
                                            ]),
                                            h('td', { key: 'amount', className: 'text-right' }, amount.toFixed(8)),
                                            h('td', { key: 'invested', className: 'text-right' }, `${invested.toFixed(2)}`),
                                            h('td', { key: 'avg-price', className: 'text-right' }, `${avgPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`),
                                            h('td', { key: 'current-price', className: 'text-right' }, `${currentPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`),
                                            h('td', { key: 'current-value', className: 'text-right' }, `${currentValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`),
                                            h('td', { 
                                                key: 'pnl', 
                                                className: `text-right ${profit >= 0 ? 'cpt-profit-positive' : 'cpt-profit-negative'}` 
                                            }, `${profit.toFixed(2)}`),
                                            h('td', { 
                                                key: 'roi',
                                                className: `text-right ${profitPercent >= 0 ? 'cpt-profit-positive' : 'cpt-profit-negative'}` 
                                            }, `${profitPercent.toFixed(2)}%`)
                                        ]);
                                    })
                                )
                            ])
                        ])
                    ])
                :
                    h('div', { 
                        key: 'empty-state',
                        className: 'cpt-empty-state' 
                    }, [
                        h('div', { 
                            key: 'icon',
                            className: 'cpt-empty-icon' 
                        }, '📊'),
                        h('h3', { 
                            key: 'title',
                            className: 'cpt-empty-title' 
                        }, 'Portfolio Vacío'),
                        h('p', { 
                            key: 'description',
                            className: 'cpt-empty-description' 
                        }, '¡Empieza añadiendo tu primera transacción para ver tu portfolio en acción!'),
                        h('button', {
                            key: 'add-button',
                            onClick: () => setShowTransactionForm(true),
                            className: 'cpt-btn cpt-btn-primary'
                        }, 'Añadir Primera Transacción')
                    ]),

                // Historial de transacciones
                transactions.length > 0 && h('div', { 
                    key: 'transactions-table',
                    className: 'cpt-glass-card' 
                }, [
                    h('h3', { 
                        key: 'title',
                        className: 'cpt-text-xl cpt-font-bold cpt-text-white cpt-mb-4' 
                    }, 'Historial de Transacciones'),
                    h('div', { 
                        key: 'table-container',
                        style: { overflowX: 'auto' }
                    }, [
                        h('table', { 
                            key: 'table',
                            className: 'cpt-table' 
                        }, [
                            h('thead', { key: 'thead' }, [
                                h('tr', { key: 'header-row' }, [
                                    h('th', { key: 'date' }, 'Fecha'),
                                    h('th', { key: 'crypto' }, 'Crypto'),
                                    h('th', { key: 'type' }, 'Tipo'),
                                    h('th', { key: 'quantity', className: 'text-right' }, 'Cantidad'),
                                    h('th', { key: 'price', className: 'text-right' }, 'Precio'),
                                    h('th', { key: 'total', className: 'text-right' }, 'Total'),
                                    h('th', { key: 'actions' }, 'Acciones')
                                ])
                            ]),
                            h('tbody', { key: 'tbody' }, 
                                transactions.map((tx, index) => {
                                    const totalValue = parseFloat(tx.total_value || 0);
                                    const amount = parseFloat(tx.amount || 0);
                                    const price = parseFloat(tx.price_per_coin || 0);

                                    return h('tr', { 
                                        key: tx.id || index
                                    }, [
                                        h('td', { key: 'date' }, tx.transaction_date ? tx.transaction_date.split(' ')[0] : ''),
                                        h('td', { key: 'crypto', className: 'cpt-font-bold' }, tx.coin_symbol),
                                        h('td', { key: 'type' }, [
                                            h('span', {
                                                style: {
                                                    color: tx.transaction_type === 'buy' ? '#10b981' : '#ef4444',
                                                    fontWeight: 'bold'
                                                }
                                            }, tx.transaction_type === 'buy' ? '🟢 Compra' : '🔴 Venta')
                                        ]),
                                        h('td', { key: 'quantity', className: 'text-right' }, amount.toFixed(8)),
                                        h('td', { key: 'price', className: 'text-right' }, `${price.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`),
                                        h('td', { key: 'total', className: 'text-right' }, `${totalValue.toFixed(2)}`),
                                        h('td', { key: 'actions' }, [
                                            h('div', {
                                                className: 'cpt-flex cpt-gap-4',
                                                style: { gap: '0.5rem' }
                                            }, [
                                                h('button', {
                                                    key: 'edit',
                                                    onClick: () => startEdit(tx),
                                                    style: {
                                                        background: 'rgba(59, 130, 246, 0.6)',
                                                        color: 'white',
                                                        border: 'none',
                                                        borderRadius: '0.25rem',
                                                        padding: '0.25rem 0.5rem',
                                                        fontSize: '0.75rem',
                                                        cursor: 'pointer'
                                                    },
                                                    onMouseEnter: (e) => e.target.style.background = 'rgba(59, 130, 246, 0.8)',
                                                    onMouseLeave: (e) => e.target.style.background = 'rgba(59, 130, 246, 0.6)'
                                                }, '✏️'),
                                                h('button', {
                                                    key: 'delete',
                                                    onClick: () => handleDeleteTransaction(tx.id),
                                                    style: {
                                                        background: 'rgba(239, 68, 68, 0.6)',
                                                        color: 'white',
                                                        border: 'none',
                                                        borderRadius: '0.25rem',
                                                        padding: '0.25rem 0.5rem',
                                                        fontSize: '0.75rem',
                                                        cursor: 'pointer'
                                                    },
                                                    onMouseEnter: (e) => e.target.style.background = 'rgba(239, 68, 68, 0.8)',
                                                    onMouseLeave: (e) => e.target.style.background = 'rgba(239, 68, 68, 0.6)'
                                                }, '🗑️')
                                            ])
                                        ])
                                    ]);
                                })
                            )
                        ])
                    ])
                ]),

                // Alerta si Recharts no está disponible
                !hasRecharts && h('div', {
                    key: 'recharts-warning',
                    style: {
                        background: 'rgba(251, 191, 36, 0.2)',
                        border: '1px solid rgba(251, 191, 36, 0.5)',
                        borderRadius: '0.5rem',
                        padding: '1rem',
                        color: '#fbbf24',
                        textAlign: 'center'
                    }
                }, 'Los gráficos están cargando... Si no aparecen, recarga la página.')
            ])
        ]);
    };

    // Inicializar la aplicación cuando el DOM esté listo
    $(document).ready(function() {
        const container = document.getElementById('crypto-portfolio-dashboard');
        if (container && window.wp && window.wp.element) {
            console.log('CPT: Inicializando dashboard React con Recharts');
            
            // Verificar si tenemos createRoot (React 18) o usar render (React 17)
            if (wp.element.createRoot) {
                const root = wp.element.createRoot(container);
                root.render(h(CryptoDashboard));
            } else {
                wp.element.render(h(CryptoDashboard), container);
            }
        } else {
            console.error('CPT: Contenedor o dependencias no encontradas', {
                container: !!container,
                wpElement: !!(window.wp && window.wp.element),
                cptAjax: !!window.cptAjax
            });
            
            // Mostrar mensaje de error en el contenedor si existe
            if (container) {
                container.innerHTML = '<div style="padding: 2rem; text-align: center; color: #dc3545; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.375rem;"><h3>Error al cargar el Dashboard</h3><p>No se pudieron cargar las dependencias necesarias. Verifica que WordPress y el plugin estén configurados correctamente.</p></div>';
            }
        }
    });

})(jQuery);