/*
Template Name: Alim - Academic Learning & Information Management
Author: gunawan_wawan43
Website: https://gunawan_wawan43.com/
Contact: gunawan_wawan43@gmail.com
File: Project overview init js
*/

// favourite btn
var favouriteBtn = document.querySelectorAll(".favourite-btn");
if (favouriteBtn) {
    Array.from(document.querySelectorAll(".favourite-btn")).forEach(function (item) {
        item.addEventListener("click", function (event) {
            this.classList.toggle("active");
        });
    });
}