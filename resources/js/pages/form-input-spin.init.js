/*
Template Name: Alim - Academic Learning & Information Management
Author: gunawan_wawan43
Website: https://gunawan_wawan43.com/
Contact: gunawan_wawan43@gmail.com
File: Form input spin Js File
*/

// input spin
isData();

function isData() {
    var plus = document.getElementsByClassName('plus');
    var minus = document.getElementsByClassName('minus');
    var product = document.getElementsByClassName("product");

    if (plus) {
        Array.from(plus).forEach(function (e) {
            e.addEventListener('click', function (event) {
                // if(event.target.previousElementSibling.value )
                if (parseInt(e.previousElementSibling.value) < event.target.previousElementSibling.getAttribute('max')) {
                    event.target.previousElementSibling.value++;
                    if (product) {
                        Array.from(product).forEach(function (x) {
                            updateQuantity(event.target);
                        })
                    }

                }
            });
        });
    }

    if (minus) {
        Array.from(minus).forEach(function (e) {
            e.addEventListener('click', function (event) {
                if (parseInt(e.nextElementSibling.value) > event.target.nextElementSibling.getAttribute('min')) {
                    event.target.nextElementSibling.value--;
                    if (product) {
                        Array.from(product).forEach(function (x) {
                            updateQuantity(event.target);
                        })
                    }
                }
            });
        });
    }
}