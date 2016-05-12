jQuery.noConflict();
var $j = jQuery.noConflict();
//CUIDANDO DE CONFLITOS COM O PROTOTYPE VS BOOTSTRAP
if (Prototype.BrowserFeatures.ElementExtensions) {
    var disablePrototypeJS = function (method, pluginsToDisable) {
            var handler = function (event) {
                event.target[method] = undefined;
                setTimeout(function () {
                    delete event.target[method];
                }, 0);
            };
            pluginsToDisable.each(function (plugin) { 
                jQuery(window).on(method + '.bs.' + plugin, handler);
            });
        },
        pluginsToDisable = ['collapse', 'dropdown', 'modal', 'tooltip', 'popover'];
    disablePrototypeJS('show', pluginsToDisable);
    disablePrototypeJS('hide', pluginsToDisable);
}