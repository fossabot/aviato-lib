// Copyright 2014-present Aviato Soft. All Rights Reserved.
const remedial = require('../../assets/js/remedial.js');
const avi = require('../../assets/js/aviato-node.js');


//aviato.fn.atos
test ('aviato.fn.atos', () => {
	let data = {
		pattern: '<div id="{id}">{text}</div>',
		objArray:[{
				'id':1,
				'text': 'one'
			},
			{
				'id': 2,
				'text': 'two'
			}],
		expected : '<div id="1">one</div><div id="2">two</div>'
	}

	expect (avi.fn.atos(data.objArray, data.pattern)).toBe(data.expected);
});


//aviato.fn.arrayMap
test ('aviato.fn.arrayMap', () => {
	let data = {
		names: ['one', 'two'],
		values: [1, 2],
		expected: {'one':1, 'two':2}
	}

	expect (avi.fn.arrayMap(data.names, data.values)).toEqual(data.expected);
});


//aviato.fn.getUrlVars
test ('aviato.fn.getUrlVars', () => {
	let data = {
		'url': 'https://www.aviato.ro/index.php?name=aviato&one=1#avi',
		'expected': ["name", "one"]
	};

	expect (avi.fn.getUrlVars(data.url)).toEqual(expect.arrayContaining(data.expected));

	delete window.location
	window.location = new URL(data.url);

	expect (avi.fn.getUrlVars()).toEqual(expect.arrayContaining(data.expected));
});


//aviato.fn.formToLocalStorage
test('aviato.fn.formToLocalStorage() and aviato.fn.localStorageToForm()', () => {
	let data = {
		'html': '<form action="/" id="frmTest">' +

			'<p>' +
			'<label for="txtOne">One</label>' +
			'<input type="text" id="txtOne" name="txtOne" value="1" />' +
			'</p>' +

			'<p>' +
			'<label for="selTwo">Two</label>'+
			'<select id="selTwo" name="selTwo">' +
			'<option value="a">A</option>' +
			'<option value="b" selected="selected">B</option>' +
			'<option value="c">C</option>' +
			'<option value="d">D</option>' +
			'</select>' +
			'</p>'+

			'<p>' +
			'<input type="submit" value="Submit"/>'+
			'</p>' +
			'</form>',

		'expected': {
			'#frmTest': '[{"name":"txtOne","value":"1"},{"name":"selTwo","value":"b"}]'
		}
	}

	document.body.innerHTML = data.html;
	expect(avi.fn.formToLocalStorage('#frmTest')).toBeUndefined();
	expect(localStorage).toEqual(data.expected);

	expect(avi.fn.localStorageToForm()).toBeUndefined();
	//changing form values:
	const $ = require('jquery');
	$('#txtOne').val('One');
	$('#selTwo').val('c');
	expect(avi.fn.localStorageToForm('#frmTest')).toBeUndefined();
	expect($('#txtOne').val()).toBe('1');
	expect($('#selTwo').val()).toBe('b');
});


/*
aviato.fn.formToLocalStorage = function(selector) {
	var oFormValues = $(selector).serializeArray();
	localStorage.setItem(selector, JSON.stringify(oFormValues));
};
*/

