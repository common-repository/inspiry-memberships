/**
 * Membership JS file.
 *
 * @since 1.0.0
 */
( function ( $ ) {
    "use strict";

    /**
     *  Dashboard Checkout form scripts.
     */

    $( document ).ready( function () {

        // Checkout form elements.
        let checkoutForm = $( '#ims-checkout-form' ),
            loader       = checkoutForm.find( '.ims-form-loader' ),
            responseLog  = checkoutForm.find( '.checkout-form-response-log' ),
            packageID    = checkoutForm.find( 'input[name="package_id"]' ).val();

        /*************************************************************
         * Payment processing methods based on selected payment method
         * **********************************************************/

            // Process free membership
        let ims_process_free_membership = function () {

                var proceed_order = $.ajax( {
                    url      : ajaxurl,
                    type     : "POST",
                    data     : {
                        checkout_form : 1,
                        membership_id : packageID,
                        action        : 'ims_subscribe_membership',
                        nonce         : checkoutForm.find( 'input[name="membership_select_nonce"]' ).val()
                    },
                    dataType : "json"
                } );

                proceed_order.done( function ( response ) {
                    loader.hide();
                    if ( response.success ) {
                        responseLog.text( response.message );
                        checkoutForm.find( 'input[name="order_id"]' ).val( response.order_id );
                        checkoutForm.find( '#ims-submit-order' ).trigger( 'click' );
                    } else {
                        responseLog.addClass( 'error' ).text( response.message );
                    }
                } );

                proceed_order.fail( function ( jqXHR, textStatus ) {
                    responseLog.addClass( 'error' ).text( "Request failed: " + textStatus );
                } );
            };

        // Process membership with Wire/Bank Transfer payment method
        let ims_process_bank_transfer = function () {

            var proceed_order = $.ajax( {
                url      : ajaxurl,
                type     : "POST",
                data     : {
                    checkout_form : 1,
                    membership_id : packageID,
                    action        : "ims_send_wire_receipt",
                    nonce         : checkoutForm.find( 'input[name="membership_wire_nonce"]' ).val()
                },
                dataType : "json"
            } );

            proceed_order.done( function ( response ) {
                loader.hide();
                if ( response.success ) {
                    responseLog.text( response.message );
                    var queryString      = '&order_id=' + response.order_id + '&package_id=' + packageID + '&payment_method=bank_transfer';
                    window.location.href = checkoutForm.find( 'input[name="redirect"]' ).val() + queryString;
                } else {
                    responseLog.addClass( 'error' ).text( response.message );
                }
            } );

            proceed_order.fail( function ( jqXHR, textStatus ) {
                responseLog.text( "Request failed: " + textStatus );
            } );
        };

        // Process membership with Stripe payment method
        let ims_process_stripe_payment = function () {
            var checkoutButton = document.querySelector( '.stripe-checkout-btn' );

            if ( checkoutButton && 'undefined' !== typeof ( Stripe ) ) {

                checkoutButton.addEventListener( 'click', event => {

                    event.preventDefault();

                    const btn_loader    = document.querySelector( '.ims-form-loader' );
                    const stripe_key    = checkoutButton.dataset.key;
                    const membership_id = checkoutButton.dataset.membership_id;
                    const isp_nonce     = checkoutButton.dataset.nonce;
                    const stripe        = Stripe( stripe_key );

                    btn_loader.classList.add( 'active' );

                    var payment_mode = null
                    if ( checkoutForm.find( '#ims_recurring' ).is( ':checked' ) ) {
                        payment_mode = 'recurring';
                    } else {
                        payment_mode = 'one_time'
                    }

                    var stripe_payment_request = $.ajax( {
                        url      : ajaxurl,
                        type     : "POST",
                        data     : {
                            action : "generate_checkout_session",
                            membership_id,
                            isp_nonce,
                            payment_mode
                        },
                        dataType : "json"
                    } );

                    stripe_payment_request.done( function ( response ) {
                        stripe.redirectToCheckout( { sessionId : response.id } );
                    } );

                } );

            } else {
                checkoutButton.addEventListener( 'click', event => {
                    alert( 'Required Stripe library is not loaded!' );
                } );
            }
        }

        /*************************************************************
         * Actions based on the non-external payment methods selection
         * **********************************************************/

        // Process payment for selected payment method Bank Transfer.
        $( '#ims-bank-transfer-payment-btn' ).on( 'click', function ( event ) {

            event.preventDefault();

            loader.show();
            responseLog.removeClass( 'error' ).empty();

            ims_process_bank_transfer();
        } );

        // Process free subscription without any payment method.
        $( '#ims-free-membership-btn' ).on( 'click', function ( event ) {

            loader.show();
            responseLog.removeClass( 'error' ).empty();

            // Process free subscription.
            ims_process_free_membership();

            event.preventDefault();
        } );

        /********************************************************
         * Update payment button based on selected payment method.
         * ******************************************************/

            // Display stripe payment button content and add click event when Stripe payment method is selected.
        let ims_display_strip_payment_button = function () {

                var proceed_order = $.ajax( {
                    url      : ajaxurl,
                    type     : "POST",
                    data     : {
                        checkout_form : 1,
                        membership    : packageID,
                        action        : "ims_stripe_button",
                        nonce         : checkoutForm.find( 'input[name="membership_select_nonce"]' ).val()
                    },
                    dataType : "json"
                } );

                proceed_order.done( function ( response ) {
                    var stripeBtn = $( '#ims-stripe-payment-btn' );
                    if ( response.success ) {
                        let button_contents = `<button
					class="stripe-checkout-btn btn btn-primary"
					data-membership_id="${response.membership_id}"
					data-nonce="${response.isp_nonce}"
					data-key="${response.publishable_key}"
					>${response.button_label}</button>`;
                        stripeBtn.html( button_contents );
                        ims_process_stripe_payment(); // Apply the stripe payment action to the newly added stripe button.
                    } else {
                        responseLog.addClass( 'error' ).text( response.message );
                    }
                } );

                proceed_order.fail( function ( jqXHR, textStatus ) {
                    responseLog.text( "Request failed: " + textStatus );
                } );
            }

        // Display the PayPal button with its JS SDK API when PayPal payment method is selected.
        let ims_display_paypal_payment_button = function ( subscription = false ) {

            if ( subscription ) {
                paypal.Buttons( {
                    style              : {
                        layout  : 'vertical', // color:  'blue',
                        shape   : 'rect',
                        label   : 'pay',
                        tagline : false
                    },
                    createSubscription : function ( data, actions ) {
                        return actions.subscription.create( {
                            /* Creates the subscription */
                            plan_id : $( 'input[name="pp_plan_id"]' ).val()
                        } );
                    },
                    onApprove          : function ( data, actions ) {

                        jQuery.ajax( {
                            url     : ajaxurl, // Replace with the actual path to your PHP file
                            type    : 'POST',
                            data    : {
                                action          : 'ims_add_paypal_recurring_membership', // Action name for the server-side function
                                subscription_id : data.subscriptionID,
                                package_id      : checkoutForm.find( 'input[name="package_id"]' ).val()
                            },
                            success : function ( response ) {

                                let responseData = JSON.parse( response );
                                if ( responseData.redirect_url ) {
                                    window.location.href = responseData.redirect_url;
                                } else {
                                    alert( responseData );
                                }
                            },
                            error   : function ( error ) {
                                // Handle any errors from the server
                                alert( error );
                            }
                        } );
                    },
                    onError            : function ( error ) {
                        // Handle errors, e.g., display an error message to the user
                        alert( error );
                    }
                } ).render( '#ims-paypal-payment-btn' );
            } else {
                paypal.Buttons( {
                    style       : {
                        layout  : 'vertical', // color:  'blue',
                        shape   : 'rect',
                        label   : 'pay',
                        tagline : false
                    },
                    createOrder : function ( data, actions ) {
                        return jQuery.ajax( {
                            url     : ajaxurl, // Replace with the actual path to your PHP file
                            type    : 'POST',
                            data    : {
                                checkout_form : 1,
                                membership_id : checkoutForm.find( 'input[name="package_id"]' ).val(),
                                action        : 'ims_create_paypal_order', // Action name for the server-side function
                                nonce         : checkoutForm.find( 'input[name="membership_paypal_nonce"]' ).val()
                            },
                            success : function ( orderId ) {
                                // Handle the response from the server (e.g., get the order ID)
                                return orderId;
                            },
                            error   : function ( error ) {
                                // Handle any errors from the server
                                alert( error );
                            }
                        } );

                    },
                    onApprove   : function ( data, actions ) {

                        jQuery.ajax( {
                            url     : ajaxurl, // Replace with the actual path to your PHP file
                            type    : 'POST',
                            data    : {
                                action   : 'ims_complete_paypal_order_payment', // Action name for the server-side function
                                order_id : data.orderID
                            },
                            success : function ( response ) {
                                let responseData = JSON.parse( response );
                                if ( responseData.redirect_url ) {
                                    window.location.href = responseData.redirect_url;
                                } else {
                                    alert( responseData );
                                }
                            },
                            error   : function ( error ) {
                                // Handle any errors from the server
                                alert( error );
                            }
                        } );

                    },
                    onError     : function ( error ) {
                        // Handle errors, e.g., display an error message to the user
                        alert( error );
                    }
                } ).render( '#ims-paypal-payment-btn' );
            }
        }

        let updatePaymentButtons = function () {

            var form                 = $( '#ims-checkout-form' ),
                currentMethod        = form.find( 'input[name="payment_method"]:checked' ).val(),
                recurringPaymentsBtn = form.find( '#ims-recurring-wrap' ),
                bankTransferBtn      = form.find( '#ims-bank-transfer-payment-btn' ),
                stripeBtn            = form.find( '#ims-stripe-payment-btn' ),
                paypalPaymentBtn     = form.find( '#ims-paypal-payment-btn' ),
                subscription         = form.find( '#ims_recurring' ).is( ':checked' );

            responseLog.empty();

            if ( 'bank_transfer' !== currentMethod ) {
                recurringPaymentsBtn.removeClass( 'hide' );
            } else {
                recurringPaymentsBtn.addClass( 'hide' );
            }

            if ( 'stripe' === currentMethod ) {
                bankTransferBtn.addClass( 'hide' );
                paypalPaymentBtn.addClass( 'hide' ).empty();
                stripeBtn.removeClass( 'hide' );

                // Add strip payment button
                ims_display_strip_payment_button();
            } else if ( 'paypal' === currentMethod ) {
                bankTransferBtn.addClass( 'hide' );
                stripeBtn.addClass( 'hide' ).empty();
                paypalPaymentBtn.removeClass( 'hide' );

                ims_display_paypal_payment_button( subscription );
            } else if ( 'bank_transfer' === currentMethod ) {
                paypalPaymentBtn.addClass( 'hide' ).empty();
                stripeBtn.addClass( 'hide' ).empty();
                bankTransferBtn.removeClass( 'hide' );
            }
        };

        // Change form elements based on payment method change.
        updatePaymentButtons();
        $( '#payment-methods' ).on( 'change', updatePaymentButtons );

        // Adds current class to clicked payment method.
        $( '.payment-method' ).on( 'click', function () {
            $( '.image-wrap' ).removeClass( 'current' );
            $( this ).find( '.image-wrap' ).addClass( 'current' );
        } );

        $( '#ims_recurring' ).on( 'change', function ( event ) {
            let payment_method = $( 'input[name="payment_method"]:checked' ).val();
            if ( 'paypal' === payment_method ) {
                $( '#ims-paypal-payment-btn' ).empty();
                ims_display_paypal_payment_button( $( this ).is( ':checked' ) );
            }
        } );

    } );
} )( jQuery );
