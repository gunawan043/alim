/*
Template Name: Alim - Academic Learning & Information Management
Author: gunawan_wawan43
Version: 4.3.0
Website: https://gunawan_wawan43.com/
Contact: gunawan_wawan43@gmail.com
File: Common Plugins Js File
*/

//Common plugins
if (document.querySelectorAll("[toast-list]") || document.querySelectorAll('[data-choices]') || document.querySelectorAll("[data-provider]")) {
    document.writeln("<script type='text/javascript' src='https://cdn.jsdelivr.net/npm/toastify-js'></script>");
    document.writeln("<script type='text/javascript' src='build/libs/choices.js/public/assets/scripts/choices.min.js'></script>");
    document.writeln("<script type='text/javascript' src='build/libs/flatpickr/flatpickr.min.js'></script>");
}
