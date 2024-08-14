# E-Mail-Snippets

Das Plugin bietet erweiterten Zugriff auf die Lieferzeitangaben in den E-Mail-Templates von Shopware 6. Mit den zusätzlichen Variablen können Sie die Kommunikation mit Ihren Kunden verbessern und ihnen präzise Lieferzeitangaben für ihre Bestellungen (gesammelt oder je Positin) bereitstellen.


## Lieferzeitangaben gesammelt für die Bestellung

Um den spätestens Lieferzeitraum für die gesamte Bestellung anzugeben, verwenden Sie bitte folgenden Code:

```twig
{% if order.deliveries.first %}
  {% set deliveryDateEarliest = order.deliveries.first.shippingDateEarliest|format_date('short',  locale='en-GB') %}
  {% set deliveryDateLatest = order.deliveries.first.shippingDateLatest|format_date('short',  locale='en-GB') %}
  {% if deliveryDateEarliest == deliveryDateLatest %}
    {{ 'areanetbetterdeliverytime.deliverySingleDate'|trans({'%date%': deliveryDateEarliest})|sw_sanitize }}
  {% else %}
    {{ 'areanetbetterdeliverytime.deliveryDate'|trans|sw_sanitize }}: {{ deliveryDateEarliest }} - {{ deliveryDateLatest }}
  {% endif %}
{% endif %}
```

## Lieferzeitangaben je Position

Um den Lieferzeitaum je Position ausgeben, können Sie folgenden, gelb markierten Code innerhalb der entsprechenden foreach-Schleife verwenden:

```twig
{% for lineItem in order.nestedLineItems %}
    {% set nestingLevel = 0 %}
    {% set nestedItem = lineItem %}
    
    {# ... #}
    {==
    {% if order.customFields.deliveryDates %}
        {% set deliveryDate = order.customFields.deliveryDates[nestedItem.identifier] %}
        {% set itemDeliveryDateEarliest = deliveryDate.earliest|format_date('short',  locale='en-GB') %}
        {% set itemDeliveryDateLatest = deliveryDate.latest|format_date('short',  locale='en-GB') %}
        {% if itemDeliveryDateEarliest == itemDeliveryDateLatest %}
            {{ 'areanetbetterdeliverytime.deliverySingleDate'|trans({'%date%': itemDeliveryDateEarliest})|sw_sanitize }}
        {% else %}
            {{ 'areanetbetterdeliverytime.deliveryDate'|trans|sw_sanitize }}: {{ itemDeliveryDateEarliest }} - {{ itemDeliveryDateLatest }}
        {% endif %}
    {% endif %}
    ==}
{% endfor %}
```
