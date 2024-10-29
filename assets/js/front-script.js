(function( $ ) { 'use strict';

    $( document ).ready( function() {
        var BOSLDGW = {
            init: function() {

                this.redeemScript();
                this.checkoutScript();
            },

            /**
             * Redeem Script
             */
            redeemScript: function() {
                var self = this;
                $( ".wmca-redeem-points-submit" ).on( "click", function( event ) {

                    event.preventDefault();

                    var form = $( this ).parents( 'form' );

                    self.bosld_show_points_popup(form);
                    return;

                    var user_points = $( "#user-points" ).val();
                    var point_label = "Points";

                    if( user_points == '' ) {
                        self.bosld_show_not_enough_popup();
                        return false;
                    }

                    var removablePoints = $( this ).attr( 'data-removable-points' );

                    if( parseInt( bosldgw_objects.bosld_required_points ) > parseInt( user_points ) ) {
                        self.bosld_show_not_enough_popup();
                        return false;
                    }

                    swal({
                        title: bosldgw_objects.confirm_points.title,
                        text : bosldgw_objects.confirm_points.text.replace("{ACTUAL_POINTS}", removablePoints).replace("{TOTAL_POINT}", user_points),
                        icon: "warning",
                        buttons: [bosldgw_objects.confirm_points.btn_cancel_text, bosldgw_objects.confirm_points.btn_text],
                        dangerMode: true,
                        closeOnClickOutside: false,
                    }).then(function( confirmed ) {
                        if(confirmed) {
                            form.submit();
                        }
                    });
                });
            },

            /**
             * Checkout Script
             */
            checkoutScript: function() {
                var self = this;
                $( ".bos-ld-checkout-button" ).on( "click", function( event ) {
                    
                    event.preventDefault();
                    var form = $( this ).parents( "form.bos-ld-checkout" );
                    self.bosld_show_points_popup(form);
                    return;
                    var course_price    = $( ".bos-ld-course-price" ).val();
                    var user_points     = $( ".bos-ld-user-points" ).val();

                    if( typeof user_points != "undefined" && user_points == "" ) {
                        self.bosld_show_not_enough_popup();
                        return false;
                    }

                    if( parseInt( bosldgw_objects.bosld_required_points ) > parseInt( user_points ) ) {
                        self.bosld_show_not_enough_popup();
                        return false;
                    }


                    swal({
                        title: bosldgw_objects.confirm_points.title,
                        text : bosldgw_objects.confirm_points.text.replace("{ACTUAL_POINTS}", course_price).replace("{TOTAL_POINT}", user_points),
                        icon: "warning",
                        buttons: [bosldgw_objects.confirm_points.btn_cancel_text, bosldgw_objects.confirm_points.btn_text],
                        dangerMode: true,
                        closeOnClickOutside: false,
                    })
                    .then(function( confirmed ) {
                        if( confirmed ) {
                            form.submit();
                        }
                    });
                });
            },

            bosld_show_not_enough_popup: function() {
                
                swal({
                    title: bosldgw_objects.not_enough_points.title,
                    text: bosldgw_objects.not_enough_points.text,
                    icon: "warning",
                    button: bosldgw_objects.not_enough_points.btn_text,
                    dangerMode: false,
                    closeOnClickOutside: false,
                });
            },

            bosld_show_points_popup: function(form) {

                var course_points    = $("#bosldgw_course_points").val();
                var user_points     = $( ".bos-ld-user-points" ).val();

                swal2.fire({
                    title: bosldgw_objects.bos_points.title,
                    input: 'select',
                    inputPlaceholder: bosldgw_objects.bos_points.select_placeholder,
                    inputOptions: bosldgw_objects.bos_points.select_options,
                    showCancelButton: true,
                    inputValidator: (value) => {
                        return new Promise((resolve) => {
                            if (value) {
                                resolve();
                            } else {
                                resolve(bosldgw_objects.bos_points.select_empty_error)
                            }
                        })
                    },
                    text: bosldgw_objects.bos_points.text.replace("{ACTUAL_POINTS}", course_points),
                    icon: "warning",
                    button: bosldgw_objects.bos_points.btn_text,
                    dangerMode: false,
                    closeOnClickOutside: false,
                }).then(function (result) {

                    var bosldgw_point_type_id = $( "#bosldgw_point_type_id" );

                    if( result.value && result.value > 0 ) {
                        bosldgw_point_type_id.val(result.value);
                        form.submit();
                    }

                });
            }
        };

        BOSLDGW.init();
    });
})( jQuery );