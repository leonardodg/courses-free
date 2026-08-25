/**
 * Formulario da condicao availability_marketplace.
 *
 * @module moodle-availability_marketplace-form
 */
M.availability_marketplace = M.availability_marketplace || {};

M.availability_marketplace.form = Y.Object(M.core_availability.plugin);

/**
 * Ofertas disponiveis, injetadas por frontend::get_javascript_init_params.
 */
M.availability_marketplace.form.offers = null;

M.availability_marketplace.form.initInner = function(offers) {
    this.offers = offers;
};

M.availability_marketplace.form.getNode = function(json) {
    var html = '<label>' + M.util.get_string('label_offer', 'availability_marketplace') +
        ' <select name="offerid">';
    for (var i = 0; i < this.offers.length; i++) {
        html += '<option value="' + this.offers[i].id + '">' +
            Y.Escape.html(this.offers[i].name) + '</option>';
    }
    html += '</select></label>';

    var node = Y.Node.create('<span class="availability_marketplace">' + html + '</span>');

    if (json.offerid !== undefined) {
        node.one('select[name=offerid]').set('value', '' + json.offerid);
    }

    // Sem isto o formulario nao percebe a mudanca e o botao de salvar nao
    // reflete que ha algo a gravar.
    node.delegate('change', function() {
        M.core_availability.form.update();
    }, 'select');

    return node;
};

M.availability_marketplace.form.fillValue = function(value, node) {
    value.offerid = parseInt(node.one('select[name=offerid]').get('value'), 10);
};
